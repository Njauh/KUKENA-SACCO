const express = require('express');
const mysql = require('mysql2/promise');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json());

// Create MySQL Database Connection Pool
const db = mysql.createPool({
    host: 'localhost',
    user: 'root',      // Replace with your MySQL username
    password: '',      // Replace with your MySQL password
    database: 'kukena_sacco',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

/* ================= 1. AUTHENTICATION ENDPOINTS ================= */

// Staff Portal Login [source: 1]
app.post('/api/staff/login', async (req, res) => {
    const { staffId, pin } = req.body;
    try {
        const [rows] = await db.query(
            `SELECT s.staff_id, s.name, s.role, t.name as defaultTerminal, s.pin 
             FROM staff s 
             LEFT JOIN terminals t ON s.default_terminal_id = t.terminal_id 
             WHERE s.staff_id = ? AND s.pin = ?`,
            [staffId, pin]
        );
        if (rows.length === 0) {
            return res.status(401).json({ success: false, message: 'Invalid Staff Account or Security PIN' });
        }
        res.json({ success: true, staff: rows[0] });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// Customer Login & Registration [source: 3]
app.post('/api/customer/auth', async (req, res) => {
    const { action, name, phone, email, password, identifier } = req.body;
    try {
        if (action === 'register') {
            const [result] = await db.query(
                'INSERT INTO users (full_name, phone_number, email, password) VALUES (?, ?, ?, ?)',
                [name, phone, email, password]
            );
            return res.json({ success: true, user: { id: result.insertId, name, phone, email } });
        } else {
            const [rows] = await db.query(
                'SELECT user_id as id, full_name as name, phone_number as phone, email FROM users WHERE (email = ? OR phone_number = ?) AND password = ?',
                [identifier, identifier, password]
            );
            if (rows.length === 0) {
                return res.status(401).json({ success: false, message: 'Invalid customer credentials' });
            }
            return res.json({ success: true, user: rows[0] });
        }
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

/* ================= 2. TRIPS & SCHEDULES ENDPOINTS ================= */

// Get All Trips (Shared by Staff, Admin, and Booking Portals) [source: 1, 2, 3]
app.get('/api/trips', async (req, res) => {
    const { origin, dest, date } = req.query;
    try {
        let query = `
            SELECT t.trip_id as id, t.departure_time as time, 
                   t1.name as origin, t2.name as dest, 
                   CONCAT(t1.name, ' to ', t2.name) as route,
                   v.registration_number as vehicle, t.fare_amount as fare, t.status,
                   t.travel_date,
                   COALESCE(GROUP_CONCAT(b.seat_number), '') as bookedSeatsStr
            FROM trips t
            JOIN terminals t1 ON t.origin_terminal_id = t1.terminal_id
            JOIN terminals t2 ON t.destination_terminal_id = t2.terminal_id
            LEFT JOIN vehicles v ON t.vehicle_id = v.vehicle_id
            LEFT JOIN bookings b ON t.trip_id = b.trip_id
            WHERE 1=1
        `;
        const params = [];

        if (origin) { query += ` AND t1.name LIKE ?`; params.push(`%${origin}%`); }
        if (dest) { query += ` AND t2.name LIKE ?`; params.push(`%${dest}%`); }
        if (date) { query += ` AND t.travel_date = ?`; params.push(date); }

        query += ` GROUP BY t.trip_id ORDER BY t.travel_date ASC, t.trip_id ASC`;

        const [rows] = await db.query(query, params);

        const trips = rows.map(r => ({
            ...r,
            occupied: r.bookedSeatsStr ? r.bookedSeatsStr.split(',').map(Number) : [],
            passengers: []
        }));

        res.json(trips);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

// Create New Trip Schedule [source: 1, 2]
app.post('/api/trips', async (req, res) => {
    const { origin, dest, departureTime, travelDate, vehicleReg, fare, staffId } = req.body;
    try {
        // Ensure vehicle exists
        let [veh] = await db.query('SELECT vehicle_id FROM vehicles WHERE registration_number = ?', [vehicleReg]);
        let vehicleId;
        if (veh.length === 0) {
            const [newVeh] = await db.query('INSERT INTO vehicles (registration_number) VALUES (?)', [vehicleReg]);
            vehicleId = newVeh.insertId;
        } else {
            vehicleId = veh[0].vehicle_id;
        }

        // Get Terminal IDs
        const [t1] = await db.query('SELECT terminal_id FROM terminals WHERE name LIKE ?', [`%${origin}%`]);
        const [t2] = await db.query('SELECT terminal_id FROM terminals WHERE name LIKE ?', [`%${dest}%`]);

        const origId = t1.length ? t1[0].terminal_id : 1;
        const destId = t2.length ? t2[0].terminal_id : 2;

        const [result] = await db.query(
            `INSERT INTO trips (origin_terminal_id, destination_terminal_id, vehicle_id, departure_time, travel_date, fare_amount, created_by_staff_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?)`,
            [origId, destId, vehicleId, departureTime, travelDate || new Date().toISOString().split('T')[0], fare || 500, staffId || null]
        );

        res.json({ success: true, tripId: result.insertId });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

/* ================= 3. BOOKINGS & TICKETING ENDPOINTS ================= */

// Process Ticket Sale / Booking [source: 1, 3]
app.post('/api/bookings', async (req, res) => {
    const { tripId, seats, passengerName, passengerPhone, paymentMethod, mpesaCode, staffId, userId, unitFare } = req.body;
    const connection = await db.getConnection();

    try {
        await connection.beginTransaction();

        const refCode = (paymentMethod.includes('MPesa') && mpesaCode) ? mpesaCode.toUpperCase() : `KUK-${Math.floor(1000 + Math.random() * 9000)}`;

        for (let seat of seats) {
            const [bookingRes] = await connection.query(
                `INSERT INTO bookings (ticket_ref, trip_id, seat_number, passenger_name, passenger_phone, user_id, booked_by_staff_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)`,
                [refCode, tripId, seat, passengerName, passengerPhone, userId || null, staffId || null]
            );

            await connection.query(
                `INSERT INTO transactions (reference_code, booking_id, payment_method, amount, processed_by_staff_id, user_id)
                 VALUES (?, ?, ?, ?, ?, ?)`,
                [refCode, bookingRes.insertId, paymentMethod, unitFare || 500, staffId || null, userId || null]
            );
        }

        await connection.commit();
        res.json({ success: true, refCode, seats });
    } catch (err) {
        await connection.rollback();
        res.status(400).json({ success: false, message: 'Seat reservation failed: ' + err.message });
    } finally {
        connection.release();
    }
});

// Get Bookings & Financial Logs [source: 1, 2]
app.get('/api/transactions', async (req, res) => {
    try {
        const [rows] = await db.query(
            `SELECT DATE_FORMAT(t.created_at, '%h:%i %p') as time, t.reference_code as ref, 
                    b.passenger_name as passenger, t.payment_method as method, 
                    t.amount, COALESCE(s.name, 'Online Portal') as agent
             FROM transactions t
             JOIN bookings b ON t.booking_id = b.booking_id
             LEFT JOIN staff s ON t.processed_by_staff_id = s.staff_id
             ORDER BY t.transaction_id DESC`
        );
        res.json(rows);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

// Get Trip Manifest [source: 1, 2]
app.get('/api/manifest/:tripId', async (req, res) => {
    try {
        const [rows] = await db.query(
            `SELECT b.seat_number as seat, b.passenger_name as name, b.passenger_phone as phone, 
                    b.ticket_ref as ref, CONCAT('Paid (', tr.payment_method, ')') as status
             FROM bookings b
             LEFT JOIN transactions tr ON b.booking_id = tr.booking_id
             WHERE b.trip_id = ? ORDER BY b.seat_number ASC`,
            [req.params.tripId]
        );
        res.json(rows);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

// Dispatch Vehicle [source: 1]
app.put('/api/trips/:id/dispatch', async (req, res) => {
    try {
        await db.query('UPDATE trips SET status = "Dispatched" WHERE trip_id = ?', [req.params.id]);
        res.json({ success: true });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

const PORT = 5000;
app.listen(PORT, () => console.log(`Kukena SACCO Unified Backend API live on port ${PORT}`));