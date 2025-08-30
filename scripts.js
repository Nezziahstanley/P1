document.addEventListener('DOMContentLoaded', () => {
    // Existing code for userType toggle
    const userTypeSelect = document.getElementById('userType');
    const studentFields = document.getElementById('studentFields');
    const staffFields = document.getElementById('staffFields');
    if (userTypeSelect && studentFields && staffFields) {
        userTypeSelect.addEventListener('change', () => {
            studentFields.style.display = userTypeSelect.value === 'student' ? 'block' : 'none';
            staffFields.style.display = userTypeSelect.value === 'staff' ? 'block' : 'none';
        });
    }

    // Load profile data for dashboards and profile page
    const profilePicture = document.getElementById('userProfilePicture');
    const currentProfilePicture = document.getElementById('currentProfilePicture');
    const userFullName = document.getElementById('userFullName');
    const userUsername = document.getElementById('userUsername');
    const userEmail = document.getElementById('userEmail');
    const userRole = document.getElementById('userRole');
    const userLevel = document.getElementById('userLevel');
    if (profilePicture || userFullName || currentProfilePicture) {
        fetch('api.php?action=getProfile')
            .then(response => {
                console.log('Get profile response status:', response.status);
                if (!response.ok) throw new Error(`Failed to fetch profile: ${response.status}`);
                return response.text();
            })
            .then(text => {
                console.log('Get profile raw response:', text);
                return JSON.parse(text);
            })
            .then(data => {
                console.log('Get profile parsed data:', data);
                if (data.error) {
                    console.error('Profile error:', data.error);
                    return;
                }
                if (userFullName) userFullName.textContent = data.fullName || '';
                if (userUsername) userUsername.textContent = data.username || '';
                if (userEmail) userEmail.textContent = data.email || '';
                if (userRole) userRole.textContent = data.role || data.userType;
                if (userLevel) userLevel.textContent = data.level || '';
                if (profilePicture && data.profilePicture) {
                    profilePicture.src = data.profilePicture;
                    profilePicture.style.display = 'block';
                }
                if (currentProfilePicture && data.profilePicture) {
                    currentProfilePicture.src = data.profilePicture;
                    currentProfilePicture.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error fetching profile:', error);
                alert('Error loading profile: ' + error.message);
            });
    }

    // Profile form handling
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(profileForm);
            try {
                console.log('Submitting profile form to api.php?action=updateProfile');
                const response = await fetch('api.php?action=updateProfile', {
                    method: 'POST',
                    body: formData
                });
                console.log('Profile response status:', response.status);
                const responseText = await response.text();
                console.log('Profile raw response:', responseText);
                if (!response.ok) throw new Error(`Profile update failed: ${response.status}, Response: ${responseText}`);
                const result = JSON.parse(responseText);
                console.log('Profile parsed response:', result);
                if (result.success) {
                    alert(result.message || 'Profile updated successfully');
                    window.location.reload();
                } else {
                    alert(result.error || 'Failed to update profile');
                }
            } catch (error) {
                console.error('Profile update error:', error);
                alert('Error updating profile: ' + error.message);
            }
        });
    }

    // Load books for dashboards
    async function loadBooks() {
        const tableBody = document.querySelector('#booksTable tbody');
        if (tableBody) {
            try {
                console.log('Fetching books from api.php?action=searchBooks&query=');
                const response = await fetch('api.php?action=searchBooks&query=');
                console.log('Books response status:', response.status);
                const responseText = await response.text();
                console.log('Books raw response:', responseText);
                if (!response.ok) throw new Error(`Failed to load books: ${response.status}, Response: ${responseText}`);
                let books;
                try {
                    books = JSON.parse(responseText);
                } catch (error) {
                    throw new Error(`Invalid JSON response: ${responseText}`);
                }
                console.log('Books parsed response:', books);
                if (!Array.isArray(books)) {
                    if (books.error) {
                        throw new Error(`API error: ${books.error}`);
                    }
                    console.warn('Books response is not an array, setting to empty array');
                    books = [];
                }
                tableBody.innerHTML = '';
                if (books.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="3">No books found</td></tr>';
                } else {
                    books.forEach(book => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${book.title}</td>
                            <td>${book.author}</td>
                            <td>
                                ${book.available && !book.activeReservations ? 
                                    `<button onclick="borrowBook(${book.id})">Borrow</button>
                                     <button onclick="reserveBook(${book.id})">Reserve</button>` : 
                                    'Not Available'}
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                }
            } catch (error) {
                console.error('Error loading books:', error);
                alert('Error loading books: ' + error.message);
            }
        }
    }

    window.borrowBook = async function(bookId) {
        if (!Number.isInteger(bookId) || bookId <= 0) {
            console.error('Invalid bookId:', bookId);
            alert('Error: Invalid book ID');
            return;
        }
        try {
            console.log(`Borrowing book ${bookId}`);
            const response = await fetch('api.php?action=borrowBook', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ bookId: bookId })
            });
            console.log('Borrow response status:', response.status);
            const responseText = await response.text();
            console.log('Borrow raw response:', responseText);
            if (!response.ok) throw new Error(`Borrow failed: ${response.status}, Response: ${responseText}`);
            const result = JSON.parse(responseText);
            console.log('Borrow parsed response:', result);
            if (result.success) {
                alert(result.message || 'Book borrowed successfully');
                loadBooks();
                if (document.getElementById('borrowingsTable')) {
                    loadBorrowings();
                } else if (document.getElementById('userBorrowingsTable')) {
                    loadUserBorrowings();
                }
            } else {
                alert(result.error || 'Failed to borrow book');
            }
        } catch (error) {
            console.error('Borrow error:', error);
            alert('Error borrowing book: ' + error.message);
        }
    };

    window.reserveBook = async function(bookId) {
        if (!Number.isInteger(bookId) || bookId <= 0) {
            console.error('Invalid bookId:', bookId);
            alert('Error: Invalid book ID');
            return;
        }
        try {
            console.log(`Reserving book ${bookId}`);
            const response = await fetch('api.php?action=reserveBook', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ bookId: bookId })
            });
            console.log('Reserve response status:', response.status);
            const responseText = await response.text();
            console.log('Reserve raw response:', responseText);
            if (!response.ok) throw new Error(`Reserve failed: ${response.status}, Response: ${responseText}`);
            const result = JSON.parse(responseText);
            console.log('Reserve parsed response:', result);
            if (result.success) {
                alert(result.message || 'Book reserved successfully');
                loadBooks();
            } else {
                alert(result.error || 'Failed to reserve book');
            }
        } catch (error) {
            console.error('Reserve error:', error);
            alert('Error reserving book: ' + error.message);
        }
    };

    // Load borrowings for manage-borrowing.html (librarian)
    async function loadBorrowings() {
        const tableBody = document.querySelector('#borrowingsTable tbody');
        if (tableBody) {
            try {
                console.log('Fetching borrowings from api.php?action=getBorrowings');
                const response = await fetch('api.php?action=getBorrowings');
                console.log('Borrowings response status:', response.status);
                const responseText = await response.text();
                console.log('Borrowings raw response:', responseText);
                if (!response.ok) throw new Error(`Failed to load borrowings: ${response.status}, Response: ${responseText}`);
                const borrowings = JSON.parse(responseText);
                console.log('Borrowings parsed response:', borrowings);
                if (!Array.isArray(borrowings)) {
                    console.warn('Borrowings response is not an array, setting to empty array');
                    tableBody.innerHTML = '<tr><td colspan="5">No borrowings found</td></tr>';
                    return;
                }
                tableBody.innerHTML = '';
                borrowings.forEach(borrowing => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${borrowing.username}</td>
                        <td>${borrowing.title}</td>
                        <td>${borrowing.borrowDate}</td>
                        <td>${borrowing.dueDate}</td>
                        <td><button onclick="returnBook(${borrowing.id}, ${borrowing.bookId})">Return</button></td>
                    `;
                    tableBody.appendChild(row);
                });
            } catch (error) {
                console.error('Error loading borrowings:', error);
                alert('Error loading borrowings: ' + error.message);
            }
        }
    }

    // Load user borrowings for staff/student dashboards
    async function loadUserBorrowings() {
        const tableBody = document.querySelector('#userBorrowingsTable tbody');
        if (tableBody) {
            try {
                console.log('Fetching user borrowings from api.php?action=getUserBorrowings');
                const response = await fetch('api.php?action=getUserBorrowings');
                console.log('User borrowings response status:', response.status);
                const responseText = await response.text();
                console.log('User borrowings raw response:', responseText);
                if (!response.ok) throw new Error(`Failed to load user borrowings: ${response.status}, Response: ${responseText}`);
                const borrowings = JSON.parse(responseText);
                console.log('User borrowings parsed response:', borrowings);
                if (!Array.isArray(borrowings)) {
                    console.warn('User borrowings response is not an array, setting to empty array');
                    tableBody.innerHTML = '<tr><td colspan="4">No borrowings found</td></tr>';
                    return;
                }
                tableBody.innerHTML = '';
                borrowings.forEach(borrowing => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${borrowing.title}</td>
                        <td>${borrowing.borrowDate}</td>
                        <td>${borrowing.dueDate}</td>
                        <td>Active</td>
                    `;
                    tableBody.appendChild(row);
                });
            } catch (error) {
                console.error('Error loading user borrowings:', error);
                alert('Error loading user borrowings: ' + error.message);
            }
        }
    }

    window.returnBook = async function(borrowingId, bookId) {
        if (!Number.isInteger(borrowingId) || borrowingId <= 0 || !Number.isInteger(bookId) || bookId <= 0) {
            console.error('Invalid borrowingId or bookId:', borrowingId, bookId);
            alert('Error: Invalid borrowing or book ID');
            return;
        }
        try {
            console.log(`Returning book ${bookId}, borrowing ${borrowingId}`);
            const response = await fetch('api.php?action=returnBook', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ borrowingId: borrowingId, bookId: bookId })
            });
            console.log('Return response status:', response.status);
            const responseText = await response.text();
            console.log('Return raw response:', responseText);
            if (!response.ok) throw new Error(`Return failed: ${response.status}, Response: ${responseText}`);
            const result = JSON.parse(responseText);
            console.log('Return parsed response:', result);
            if (result.success) {
                alert(result.message || 'Book returned successfully');
                loadBorrowings();
            } else {
                alert(result.error || 'Failed to return book');
            }
        } catch (error) {
            console.error('Return error:', error);
            alert('Error returning book: ' + error.message);
        }
    };

    // Load reservations for manage-reservations.html
    async function loadReservations() {
        const tableBody = document.querySelector('#reservationsTable tbody');
        if (tableBody) {
            try {
                console.log('Fetching reservations from api.php?action=getReservations');
                const response = await fetch('api.php?action=getReservations');
                console.log('Reservations response status:', response.status);
                const responseText = await response.text();
                console.log('Reservations raw response:', responseText);
                if (!response.ok) throw new Error(`Failed to load reservations: ${response.status}, Response: ${responseText}`);
                const reservations = JSON.parse(responseText);
                console.log('Reservations parsed response:', reservations);
                if (!Array.isArray(reservations)) {
                    console.warn('Reservations response is not an array, setting to empty array');
                    tableBody.innerHTML = '<tr><td colspan="5">No reservations found</td></tr>';
                    return;
                }
                tableBody.innerHTML = '';
                reservations.forEach(reservation => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${reservation.username}</td>
                        <td>${reservation.title}</td>
                        <td>${reservation.reservationDate}</td>
                        <td>${reservation.status}</td>
                        <td>
                            ${reservation.status === 'pending' ? 
                                `<button onclick="approveReservation(${reservation.id})">Approve</button>
                                 <button onclick="cancelReservation(${reservation.id})">Cancel</button>` : 
                                reservation.status}
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
            } catch (error) {
                console.error('Error loading reservations:', error);
                alert('Error loading reservations: ' + error.message);
            }
        }
    }

    window.approveReservation = async function(reservationId) {
        if (!Number.isInteger(reservationId) || reservationId <= 0) {
            console.error('Invalid reservationId:', reservationId);
            alert('Error: Invalid reservation ID');
            return;
        }
        try {
            console.log(`Approving reservation ${reservationId}`);
            const response = await fetch('api.php?action=approveReservation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ reservationId: reservationId })
            });
            console.log('Approve response status:', response.status);
            const responseText = await response.text();
            console.log('Approve raw response:', responseText);
            if (!response.ok) throw new Error(`Approve failed: ${response.status}, Response: ${responseText}`);
            const result = JSON.parse(responseText);
            console.log('Approve parsed response:', result);
            if (result.success) {
                alert(result.message || 'Reservation approved');
                loadReservations();
            } else {
                alert(result.error || 'Failed to approve reservation');
            }
        } catch (error) {
            console.error('Approve error:', error);
            alert('Error approving reservation: ' + error.message);
        }
    };

    window.cancelReservation = async function(reservationId) {
        if (!Number.isInteger(reservationId) || reservationId <= 0) {
            console.error('Invalid reservationId:', reservationId);
            alert('Error: Invalid reservation ID');
            return;
        }
        try {
            console.log(`Cancelling reservation ${reservationId}`);
            const response = await fetch('api.php?action=cancelReservation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ reservationId: reservationId })
            });
            console.log('Cancel response status:', response.status);
            const responseText = await response.text();
            console.log('Cancel raw response:', responseText);
            if (!response.ok) throw new Error(`Cancel failed: ${response.status}, Response: ${responseText}`);
            const result = JSON.parse(responseText);
            console.log('Cancel parsed response:', result);
            if (result.success) {
                alert(result.message || 'Reservation cancelled');
                loadReservations();
            } else {
                alert(result.error || 'Failed to cancel reservation');
            }
        } catch (error) {
            console.error('Cancel error:', error);
            alert('Error cancelling reservation: ' + error.message);
        }
    };

    // Load users for manage-users.html
    async function loadUsers() {
        const tableBody = document.querySelector('#usersTable tbody');
        if (tableBody) {
            try {
                console.log('Fetching users from api.php?action=getUsers');
                const response = await fetch('api.php?action=getUsers');
                console.log('Users response status:', response.status);
                const responseText = await response.text();
                console.log('Users raw response:', responseText);
                if (!response.ok) throw new Error(`Failed to load users: ${response.status}, Response: ${responseText}`);
                const users = JSON.parse(responseText);
                console.log('Users parsed response:', users);
                if (!Array.isArray(users)) {
                    console.warn('Users response is not an array, setting to empty array');
                    tableBody.innerHTML = '<tr><td colspan="6">No users found</td></tr>';
                    return;
                }
                tableBody.innerHTML = '';
                users.forEach(user => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${user.profilePicture ? `<img src="${user.profilePicture}" class="profile-picture" style="width: 50px; height: 50px;">` : 'No Image'}</td>
                        <td>${user.fullName}</td>
                        <td>${user.username}</td>
                        <td>${user.email}</td>
                        <td>${user.role || user.userType}</td>
                        <td><a href="edit-user.html?userId=${user.id}" class="btn">Edit</a></td>
                    `;
                    tableBody.appendChild(row);
                });
            } catch (error) {
                console.error('Error loading users:', error);
                alert('Error loading users: ' + error.message);
            }
        }
    }

    // Initialize tables
    if (document.getElementById('booksTable')) loadBooks();
    if (document.getElementById('usersTable')) loadUsers();
    if (document.getElementById('borrowingsTable')) loadBorrowings();
    if (document.getElementById('reservationsTable')) loadReservations();
    if (document.getElementById('userBorrowingsTable')) loadUserBorrowings();

    // Registration form handling
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(registerForm);
            const formDataObj = Object.fromEntries(formData);
            console.log('Register form data:', formDataObj);
            try {
                console.log('Submitting register form to ./api.php?action=register');
                const response = await fetch('./api.php?action=register', {
                    method: 'POST',
                    body: formData
                });
                console.log('Register response status:', response.status);
                const responseText = await response.text();
                console.log('Register raw response:', responseText);
                if (!response.ok) {
                    throw new Error(`Network error: ${response.status}, Response: ${responseText}`);
                }
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (error) {
                    throw new Error(`Invalid JSON response: ${responseText}`);
                }
                console.log('Register parsed response:', result);
                if (result.success) {
                    alert(result.message || 'Registration successful');
                    console.log('Redirecting to ./login.html');
                    window.location.href = './login.html';
                } else {
                    alert(result.error || 'Registration failed');
                }
            } catch (error) {
                console.error('Registration error:', error);
                alert('Error during registration: ' + error.message);
            }
        });
    }

    // Login form handling
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(loginForm);
            const formDataObj = Object.fromEntries(formData);
            console.log('Login form data:', formDataObj);
            try {
                console.log('Submitting login form to ./api.php?action=login');
                const response = await fetch('./api.php?action=login', {
                    method: 'POST',
                    body: formData
                });
                console.log('Login response status:', response.status);
                const responseText = await response.text();
                console.log('Login raw response:', responseText);
                if (!response.ok) {
                    throw new Error(`Network error: ${response.status}, Response: ${responseText}`);
                }
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (error) {
                    throw new Error(`Invalid JSON response: ${responseText}`);
                }
                console.log('Login parsed response:', result);
                if (result.success) {
                    console.log('Login successful, userType:', result.userType);
                    if (result.userType === 'student') {
                        console.log('Redirecting to ./student-dashboard.html');
                        window.location.href = './student-dashboard.html';
                    } else if (result.userType === 'staff') {
                        console.log('Redirecting to ./staff-dashboard.html');
                        window.location.href = './staff-dashboard.html';
                    } else if (result.userType === 'librarian') {
                        console.log('Redirecting to ./librarian-dashboard.html');
                        window.location.href = './librarian-dashboard.html';
                    } else {
                        throw new Error('Invalid userType in response: ' + result.userType);
                    }
                } else {
                    alert(result.error || 'Login failed. Please try again.');
                }
            } catch (error) {
                console.error('Login error:', error);
                alert('Error during login: ' + error.message);
            }
        });
    }
});