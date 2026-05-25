-- Drop and recreate database to avoid foreign key issues
DROP DATABASE IF EXISTS hospital_db;
CREATE DATABASE hospital_db;
USE hospital_db;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    dob DATE,
    gender ENUM('Male', 'Female', 'Other'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create hospitals table
CREATE TABLE hospitals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    specialties TEXT NOT NULL,
    rating FLOAT NOT NULL,
    slots_available INT NOT NULL
);

-- Create appointments table
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hospital_id INT NOT NULL,
    department VARCHAR(100) NOT NULL,
    appointment_date DATE NOT NULL,
    time_slot VARCHAR(50) NOT NULL,
    reason TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'Scheduled',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
);

-- Create beds table
CREATE TABLE beds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT NOT NULL,
    ward_type VARCHAR(50) NOT NULL,
    total_beds INT NOT NULL,
    available_beds INT NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
);

-- Create inventory table
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    category ENUM('Medicine', 'Equipment', 'Consumable') NOT NULL,
    quantity INT NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
);

-- Insert user (Kotapati Chandeep)
INSERT INTO users (id, username, full_name, email, password, phone, address, dob, gender)
VALUES (
    1,
    'chandeep',
    'Kotapati Chandeep',
    'chandeep@example.com',
    '$2y$10$examplehashedpassword1234567890abcdef',
    '9876543210',
    '123, Vijay Nagar, Delhi',
    '1995-05-15',
    'Male'
);

-- Insert 9 hospitals
INSERT INTO hospitals (id, name, address, specialties, rating, slots_available)
VALUES
    (1, 'AIIMS', 'Ansari Nagar, Delhi', 'Cardiology,Neurology', 4.5, 10),
    (2, 'Fortis', 'Vasant Kunj, Delhi', 'Orthopedics,Pediatrics', 4.0, 8),
    (3, 'Max', 'Saket, Delhi', 'Multi-specialty', 4.2, 12),
    (4, 'Apollo', 'Sarita Vihar, Delhi', 'Oncology,Cardiology', 4.3, 15),
    (5, 'Indraprastha', 'Mathura Road, Delhi', 'Neurology,Orthopedics', 4.1, 9),
    (6, 'Ganga Ram', 'Rajinder Nagar, Delhi', 'Pediatrics,Oncology', 4.4, 11),
    (7, 'Medanta', 'Gurgaon, Delhi NCR', 'Cardiology,Neurology', 4.6, 14),
    (8, 'BLK', 'Pusa Road, Delhi', 'Orthopedics,Oncology', 4.0, 7),
    (9, 'Safdarjung', 'Safdarjung Enclave, Delhi', 'Multi-specialty', 4.2, 13);

-- Insert sample appointments (3 for Chandeep)
INSERT INTO appointments (user_id, hospital_id, department, appointment_date, time_slot, reason, status)
VALUES
    (1, 3, 'Cardiology', '2025-04-18', 'Morning', 'Chest pain', 'Scheduled'),
    (1, 9, 'Neurology', '2025-04-19', 'Afternoon', 'Headache', 'Scheduled'),
    (1, 1, 'Cardiology', '2025-04-20', 'Evening', 'Heart checkup', 'Scheduled');

-- Insert beds (50 rows, ~5-6 per hospital)
INSERT INTO beds (hospital_id, ward_type, total_beds, available_beds)
VALUES
    (1, 'General Ward', 100, 20), (1, 'ICU-1', 20, 5), (1, 'Private Room', 30, 10), (1, 'Emergency', 15, 3), (1, 'Pediatric Ward', 25, 8), (1, 'Surgical Ward', 20, 6),
    (2, 'General Ward', 80, 15), (2, 'ICU', 15, 4), (2, 'Private Room', 25, 8), (2, 'Emergency', 10, 2), (2, 'Orthopedic Ward', 20, 7),
    (3, 'General Ward', 120, 25), (3, 'ICU-1', 30, 7), (3, 'Private Room', 40, 12), (3, 'Emergency', 20, 5), (3, 'Cardiology Ward', 25, 9), (3, 'Neurology Ward', 20, 6),
    (4, 'General Ward', 90, 18), (4, 'ICU', 25, 6), (4, 'Private Room', 35, 10), (4, 'Oncology Ward', 20, 5), (4, 'Emergency', 15, 4),
    (5, 'General Ward', 70, 12), (5, 'ICU', 10, 3), (5, 'Private Room', 20, 7), (5, 'Neurology Ward', 15, 4), (5, 'Orthopedic Ward', 15, 5), (5, 'Emergency', 10, 2),
    (6, 'General Ward', 85, 16), (6, 'ICU', 18, 5), (6, 'Private Room', 28, 9), (6, 'Pediatric Ward', 20, 6), (6, 'Emergency', 12, 3),
    (7, 'General Ward', 110, 22), (7, 'ICU-1', 28, 8), (7, 'Private Room', 38, 11), (7, 'Cardiology Ward', 25, 7), (7, 'Emergency', 18, 4), (7, 'Surgical Ward', 20, 6),
    (8, 'General Ward', 60, 10), (8, 'ICU', 12, 3), (8, 'Private Room', 22, 6), (8, 'Oncology Ward', 15, 4), (8, 'Emergency', 10, 2),
    (9, 'General Ward', 130, 28), (9, 'ICU-1', 35, 9), (9, 'Private Room', 45, 14), (9, 'Neurology Ward', 25, 8), (9, 'Emergency', 22, 6), (9, 'Pediatric Ward', 30, 10), (9, 'Surgical Ward', 25, 7);

-- Insert inventory (50 rows, ~5-6 per hospital)
INSERT INTO inventory (hospital_id, item_name, category, quantity)
VALUES
    (1, 'Paracetamol', 'Medicine', 500), (1, 'Ventilator', 'Equipment', 10), (1, 'Syringes', 'Consumable', 1000), (1, 'Aspirin', 'Medicine', 300), (1, 'ECG Machine', 'Equipment', 5), (1, 'Gloves', 'Consumable', 2000),
    (2, 'Ibuprofen', 'Medicine', 400), (2, 'Defibrillator', 'Equipment', 8), (2, 'Masks', 'Consumable', 1500), (2, 'Ceftriaxone', 'Medicine', 200), (2, 'Bandages', 'Consumable', 800),
    (3, 'Amoxicillin', 'Medicine', 600), (3, 'Ultrasound Machine', 'Equipment', 4), (3, 'IV Drips', 'Consumable', 500), (3, 'Metformin', 'Medicine', 350), (3, 'Oxygen Concentrator', 'Equipment', 6), (3, 'Gauze', 'Consumable', 1200),
    (4, 'Losartan', 'Medicine', 250), (4, 'X-Ray Machine', 'Equipment', 3), (4, 'Alcohol Swabs', 'Consumable', 1000), (4, 'Doxycycline', 'Medicine', 150), (4, 'Catheters', 'Consumable', 600),
    (5, 'Salbutamol', 'Medicine', 300), (5, 'Pulse Oximeter', 'Equipment', 10), (5, 'Surgical Masks', 'Consumable', 2000), (5, 'Atenolol', 'Medicine', 450), (5, 'Needles', 'Consumable', 1500), (5, 'MRI Scanner', 'Equipment', 2),
    (6, 'Omeprazole', 'Medicine', 200), (6, 'CT Scanner', 'Equipment', 3), (6, 'Insulin', 'Medicine', 400), (6, 'IV Cannula', 'Consumable', 800), (6, 'Nebulizer', 'Equipment', 5),
    (7, 'Levothyroxine', 'Medicine', 250), (7, 'Dialysis Machine', 'Equipment', 4), (7, 'Sterile Pads', 'Consumable', 900), (7, 'Azithromycin', 'Medicine', 200), (7, 'Suture Kits', 'Consumable', 300), (7, 'Endoscope', 'Equipment', 3),
    (8, 'Cetirizine', 'Medicine', 350), (8, 'Infusion Pump', 'Equipment', 6), (8, 'Disposable Gowns', 'Consumable', 700), (8, 'Pantoprazole', 'Medicine', 150), (8, 'Thermometers', 'Equipment', 20),
    (9, 'Enalapril', 'Medicine', 300), (9, 'Anesthesia Machine', 'Equipment', 5), (9, 'Face Shields', 'Consumable', 1000), (9, 'Clopidogrel', 'Medicine', 400), (9, 'Blood Pressure Monitor', 'Equipment', 15), (9, 'Cotton Rolls', 'Consumable', 1200);