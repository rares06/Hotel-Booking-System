// Load bookings when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadBookings();
    loadHotels();
    setupDateValidation();
    setupLogout();
});

function setupLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }
}

async function handleLogout() {
    try {
        const response = await fetch('./logout.php', {
            method: 'POST',
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.href = result.redirect;
        } else {
            throw new Error(result.error || 'Logout failed');
        }
    } catch (error) {
        console.error('Logout error:', error);
        alert('Logout failed. Please try again.');
    }
}

const apiParentRoute = 'http://localhost/hotel_booking_system/server/api.php/bookings';
const apiHotelsRoute = 'http://localhost/hotel_booking_system/server/api.php/hotels';


function incrementGuests() {
    const guestCount = document.getElementById('guestCount');
    if (parseInt(guestCount.value) < 10) {
        guestCount.value = parseInt(guestCount.value) + 1;
    }
}

function decrementGuests() {
    const guestCount = document.getElementById('guestCount');
    if (parseInt(guestCount.value) > 1) {
        guestCount.value = parseInt(guestCount.value) - 1;
    }
}

function setupDateValidation() {
    const today = new Date().toISOString().split('T')[0];
    const checkIn = document.getElementById('checkIn');
    const checkOut = document.getElementById('checkOut');
    
    // Setăm data minimă pentru check-in și check-out
    checkIn.min = today;
    checkOut.min = today;
    
    // Actualizăm data minimă pentru check-out când se schimbă check-in
    checkIn.addEventListener('change', () => {
        checkOut.min = checkIn.value;
        if (checkOut.value < checkIn.value) {
            checkOut.value = checkIn.value;
        }
    });
}

// Handle form submission
const bookingForm = document.getElementById('bookingForm');
const submitButton = bookingForm.querySelector('button[type="submit"]');
let isEditing = false;
let editingId = null;

bookingForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const booking = {
        hotel_id: document.getElementById('hotel').value,
        guest_name: document.getElementById('guestName').value,
        guest_count: document.getElementById('guestCount').value,
        check_in: document.getElementById('checkIn').value,
        check_out: document.getElementById('checkOut').value
    };

    try {
        let response;
        
        if (isEditing) {
            response = await fetch(`${apiParentRoute}/${editingId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(booking)
            });
        } else {
            response = await fetch(apiParentRoute, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(booking)
            });
        }
        
        const result = await response.json();
        
        if (result.success) {
            resetForm();
            loadBookings();
        } else {
            throw new Error(result.error || 'Operation failed');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Operation failed. Please try again.');
    }
});

async function loadHotels() {
    try {
        const response = await fetch(apiHotelsRoute, {
            credentials: 'include'
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Hotels data:', result.data);
        
        if (result.success && result.data) {
            const hotelSelect = document.getElementById('hotel');
            hotelSelect.innerHTML = '<option value="">Select a hotel</option>';
            
            result.data.forEach(hotel => {
                const option = document.createElement('option');
                option.value = hotel.id;
                option.textContent = hotel.name;
                hotelSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading hotels:', error);
        alert('Failed to load hotels. Please refresh the page.');
    }
}

async function loadBookings() {
    try {
        const response = await fetch(apiParentRoute, {
            credentials: 'include'
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Bookings loaded:', result);

        const bookingsList = document.getElementById('bookingsList');
        bookingsList.innerHTML = '';  // Curăță lista de carduri

        const isAdmin = window.isAdmin;
        console.log('Is admin:', isAdmin);

        if (result.success && result.data) {
            result.data.forEach(booking => {
                const card = document.createElement('div');
                card.className = 'booking-card';
                card.innerHTML = `
                    <h3>${booking.hotel_name}</h3>
                    <p>Guest: ${booking.guest_name}</p>
                    <p>Number of Guests: ${booking.guest_count}</p>
                    <p>Check-in: ${booking.check_in}</p>
                    <p>Check-out: ${booking.check_out}</p>
                `;

                // Dacă este admin, adaugă butoanele de editare și ștergere
                if (isAdmin) {
                    const actions = document.createElement('div');
                    actions.className = 'actions';
                    actions.innerHTML = `
                        <button onclick="editBooking(${booking.id})" class="edit-btn">Edit</button>
                        <button formnovalidate onclick="deleteBooking(${booking.id}, event)" class="delete-btn">Delete</button>
                    `;
                    card.appendChild(actions);
                }

                bookingsList.appendChild(card);
            });

        } else {
            bookingsList.innerHTML = '<p>No bookings found.</p>';
        }
    } catch (error) {
        console.error('Error loading bookings:', error);
        document.getElementById('bookingsList').innerHTML = 
            '<p class="error">Error loading bookings. Please try again later.</p>';
    }
}

async function deleteBooking(id, event) {
    if (event) {
        event.preventDefault(); // Previne submit-ul formularului
    }

    if (!window.isAdmin) {
        alert('Unauthorized action');
        return;
    }

    if (confirm('Are you sure you want to delete this booking?')) {
        try {
            const response = await fetch(`${apiParentRoute}/${id}`, {
                method: 'DELETE'
            });
            
            const result = await response.json();
            
            if (result.success) {
                loadBookings();
                if (editingId === id) {
                    resetForm();
                }
            } else {
                throw new Error(result.error || 'Failed to delete booking');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to delete booking. Please try again.');
        }
    }
}

// Adăugăm buton de Cancel pentru editare
const cancelButton = document.createElement('button');
cancelButton.type = 'button';
cancelButton.textContent = 'Cancel';
cancelButton.style.display = 'none';
cancelButton.onclick = resetForm;
submitButton.parentNode.insertBefore(cancelButton, submitButton.nextSibling);

// Modificăm funcția resetForm pentru a gestiona butonul Cancel
function resetForm() {
    bookingForm.reset();
    submitButton.textContent = 'Create Booking';
    cancelButton.style.display = 'none';
    isEditing = false;
    editingId = null;
}

// Modificăm funcția editBooking pentru a afișa butonul Cancel
async function editBooking(id) {
    if (!window.isAdmin) {
        alert('Unauthorized action');
        return;
    }

    try {
        const response = await fetch(`${apiParentRoute}/${id}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const booking = result.data;
            
            document.getElementById('hotel').value = booking.hotel_id;
            document.getElementById('guestName').value = booking.guest_name;
            document.getElementById('checkIn').value = booking.check_in;
            document.getElementById('checkOut').value = booking.check_out;
            
            submitButton.textContent = 'Update Booking';
            cancelButton.style.display = 'inline-block';
            isEditing = true;
            editingId = id;
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load booking details. Please try again.');
    }
}

// Adaugă această funcție în fetch.js
async function handleLogin(formData, isAdmin = false) {
    try {
        const response = await fetch('http://localhost/hotel_booking_system/server/auth.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Content-Type': 'application/json',
            },
            credenials: 'include',
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.isAdmin = result.is_admin; // Setăm variabila globală
            window.location.href = result.redirect;
        } else {
            throw new Error(result.error || 'Login failed');
        }
    } catch (error) {
        console.error('Login error:', error);
        alert('Login failed. Please try again.');
    }
}