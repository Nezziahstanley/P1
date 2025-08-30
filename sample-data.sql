-- Select the database
USE library_db;

-- Insert sample users
INSERT INTO users (fullName, username, email, password, userType, approved, role, level) VALUES
('John Doe', 'johndoe', 'john@example.com', '$2y$10$3gX9z3X8b8z3X8b8z3X8b8z3X8b8z3X8b8z3X8b8z3X8b8z3X8b8', 'student', TRUE, NULL, 'ND1'),
('Jane Smith', 'janesmith', 'jane@example.com', '$2y$10$3gX9z3X8b8z3X8b8z3X8b8z3X8b8z3X8b8z3X8b8z3X8b8z3X8b8', 'staff', TRUE, 'Lecturer', NULL),
('Test User', 'testuser', 'test@example.com', '$2y$10$3gX9z3X8b8z3X8b8z3X8b8z3X8b8z3X8b8z3X8b8z3X8b8z3X8b8', 'student', TRUE, NULL, 'HND2'),
('Librarian Admin', 'librarian', 'librarian@example.com', '$2y$10$J9z8Y5x6K3m2W7v9Q0r1P.wXyZ2tL8u4N6a3B5c7D1e9F8g0H2i4J', 'librarian', TRUE, 'Admin', NULL);

-- Insert sample books
INSERT INTO books (title, author, isbn, available, publication_year, category, borrowerId) VALUES
('MicroSoft Excel 2019 Data analysis and business modeling', 'Wayne L. Winston', '9789389347180', TRUE, 2022, 'Computer Science', NULL),
('System analysis & design', 'Donald Yeates, Tony Wakefield', '9780273655367', TRUE, 2004, 'Computer Science', NULL),
('Procedural elements for computer graphics', 'David F. Rogers', '9780070473713', FALSE, 2019, 'Computer Science', 1),
('Comptia Network + study Guide', 'Todd Lammle', '9781118137550', TRUE, 2012, 'Computer Science', NULL),
('CISCO CCENT/CCNA', 'Wendell Odom', '9781587143854', TRUE, 2013, 'Computer Science', NULL),
('Cloud computing', 'Dr. U.S. Pandey, Dr. Kavita Choudhary', '9789383746736', TRUE, 2014, 'Computer Science', NULL),
('Computer Network', 'Tanenbaum', '9780132126953', TRUE, 2011, 'Computer Science', NULL),
('Computer architecture', 'John L. Hennessy, David A. Patterson', '9780128119051', TRUE, 2017, 'Computer Science', NULL),
('Database system concepts', 'Abraham S.', '9780073523323', TRUE, 2010, 'Computer Science', NULL),
('Microsoft Visual C# 2013 Step by step', 'John Sharp', '9780735681835', TRUE, 2013, 'Computer Science', NULL),
('Programming in ANSI C', 'E Balagurusamy', '9789352602131', FALSE, 2016, 'Computer Science', 2),
('VLS Design', 'A M Natarajan', NULL, TRUE, 2015, 'Computer Science', NULL),
('C++ Programming', 'Bjarne Stroustrup', '9780321563842', TRUE, 2013, 'Computer Science', NULL),
('Operating Systems', 'William Stallings', '9780134670959', TRUE, 2018, 'Computer Science', NULL),
('Data Structures and Algorithms', 'Alfred V. Aho, Jeffrey D. Ullman', '9780201000238', TRUE, 1983, 'Computer Science', NULL),
('Introduction to Algorithms', 'Thomas H. Cormen', '9780262033848', TRUE, 2009, 'Computer Science', NULL),
('Artificial Intelligence', 'Stuart Russell, Peter Norvig', '9780136042594', TRUE, 2010, 'Computer Science', NULL),
('Web Technologies', 'Uttam K. Roy', '9780198066224', TRUE, 2010, 'Computer Science', NULL),
('Study & thinking skills', 'Felisa L. Sapno, Eden C. Diaz', '9789712356780', TRUE, 2010, 'General Studies', NULL),
('Introduction to Psychology', 'Atkinson & Hilgard', '9780155050693', TRUE, 2009, 'General Studies', NULL),
('Motivational factors and teacher efficiency', 'Aisha D. Suleiman', '9780191234567', TRUE, 2005, 'General Studies', NULL),
('The business plan workbook', 'Colin Barrow, Paul Barrow, Robert Barrow', '9780749482312', TRUE, 2021, 'Business Administration', NULL),
('Business statistics for dummies', 'Alan Anderson', '9781118770955', TRUE, 2013, 'Business Administration', NULL),
('Marketing Management', 'Philip Kotler, Kevin Lane Keller', '9780133856460', TRUE, 2015, 'Business Administration', NULL),
('Financial Accounting', 'Jerry J. Weygandt', '9781119295808', TRUE, 2018, 'Business Administration', NULL),
('Human Resource Management', 'Gary Dessler', '9780134235455', TRUE, 2016, 'Business Administration', NULL),
('Basic Civil Engineering', 'Satheesh Gopi', '9788131729885', TRUE, 2009, 'Civil Engineering', NULL),
('Structural Analysis', 'R.C. Hibbeler', '9780134610672', TRUE, 2017, 'Civil Engineering', NULL),
('Surveying and Levelling', 'N.N. Basak', '9780074603994', TRUE, 2014, 'Civil Engineering', NULL),
('Concrete Technology', 'M.S. Shetty', '9788121903486', TRUE, 2008, 'Civil Engineering', NULL),
('Geotechnical Engineering', 'C. Venkatramaiah', '9788122400786', TRUE, 2006, 'Civil Engineering', NULL),
('Financial Accounting for dummies', 'Maire Loughran', '9780470930656', TRUE, 2011, 'Accounting', NULL),
('Cost Accounting', 'Charles T. Horngren', '9780134475585', TRUE, 2017, 'Accounting', NULL),
('Management Accounting', 'Anthony A. Atkinson', '9780137024971', TRUE, 2012, 'Accounting', NULL),
('Auditing and Assurance', 'Alvin A. Arens', '9780134065823', TRUE, 2016, 'Accounting', NULL),
('Design of electrical machines', 'K G Upadhyay', '9788122422825', TRUE, 2008, 'Electrical/Electronic Engineering', NULL),
('Electrical power systems', 'C L Wadhwa', '9788122438376', TRUE, 2017, 'Electrical/Electronic Engineering', NULL),
('Architectural Design', 'Jane Anderson', '9781856698962', TRUE, 2011, 'Architecture', NULL),
('Building Construction', 'B.C. Punmia', '9788131804285', TRUE, 2008, 'Architecture', NULL),
('History of Architecture', 'Banister Fletcher', '9780750622677', TRUE, 1996, 'Architecture', NULL),
('Statistics for Management', 'Richard I. Levin', '9780138131111', TRUE, 2011, 'Statistics', NULL),
('Probability and Statistics', 'Morris H. DeGroot', '9780321500465', TRUE, 2012, 'Statistics', NULL),
('Theory of computation', 'Michael Sipser', '9781133187790', TRUE, 2012, 'Statistics', NULL),
('Statistical Methods', 'S.P. Gupta', '9788180547621', TRUE, 2011, 'Statistics', NULL),
('Principles of Economics', 'N. Gregory Mankiw', '9781305585126', TRUE, 2017, 'Economics', NULL),
('Microeconomics', 'Paul Krugman, Robin Wells', '9781429283427', TRUE, 2015, 'Economics', NULL),
('Macroeconomics', 'Olivier Blanchard', '9780133061635', TRUE, 2017, 'Economics', NULL);

-- Insert sample reservations
INSERT INTO reservations (userId, bookId, reservationDate, status) VALUES
(1, 3, '2025-07-30 10:00:00', 'pending'),
(2, 11, '2025-07-29 15:30:00', 'approved'),
(1, 16, '2025-07-28 12:00:00', 'pending'),
(2, 6, '2025-07-27 14:00:00', 'approved');

-- Insert sample announcements
INSERT INTO announcements (title, content) VALUES
('Library Closure', 'The library will be closed on August 1, 2025, for maintenance.'),
('New Books Added', 'Check out our new arrivals in the Technology section!');

-- Insert sample notifications
INSERT INTO notifications (librarianId, userId, message, createdAt, isRead) VALUES
(4, 1, 'New user registered: John Doe (johndoe)', '2025-07-30 09:00:00', FALSE),
(4, 2, 'New user registered: Jane Smith (janesmith)', '2025-07-30 09:00:00', FALSE);