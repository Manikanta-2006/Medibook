<?php
session_start();
require 'php/config.php';

$stmt = $pdo->query("SELECT * FROM hospitals LIMIT 9");
$hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hospital_images = [
  1 => 'images/aiims.webp',
  2 => 'images/fortis.jpg',
  3 => 'images/max.webp',
  4 => 'images/apolo.jpg',
  5 => 'images/indra.jpg',
  6 => 'images/ganga.jpg',
  7 => 'images/medanta.jpg',
  8 => 'images/bkl.jpg',
  9 => 'images/saf.avif',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delhi Hospitals - Book Appointments</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .animate-fadeIn { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .hospital-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
  </style>
</head>
<body class="min-h-screen bg-gray-50">
  <!-- Header -->
  <header class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
      <div class="flex items-center">
        <h1 class="text-2xl font-bold text-gray-900">MediBook</h1>
      </div>
      <nav class="flex space-x-4">
        <a href="#" class="bg-blue-100 text-blue-700 px-3 py-2 rounded-md text-sm font-medium">Hospitals</a>
        <?php if (isset($_SESSION['user_id'])): ?>
          <div id="user-profile">
            <div class="flex items-center space-x-2">
              <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700">
                <span id="user-initials"><?php echo htmlspecialchars(substr($_SESSION['username'], 0, 1)); ?></span>
              </div>
              <div class="relative group">
                <button id="profile-button" class="text-gray-700 hover:text-blue-600 font-medium">
                  <span id="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </button>
                <div id="profile-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 animate-fadeIn">
                  <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
                  <button id="logout-button" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</button>
                </div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div id="auth-links">
            <a href="login.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Login</a>
            <a href="signup.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Sign Up</a>
          </div>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <!-- Hero Section -->
  <div class="bg-blue-600 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
      <h2 class="text-3xl md:text-4xl font-bold mb-4">Find and Book Hospital Appointments in Delhi</h2>
      <p class="text-xl text-blue-100 mb-8">Schedule appointments with top hospitals and medical facilities in Delhi</p>
      <div class="max-w-3xl bg-white p-4 rounded-lg shadow-lg flex items-center space-x-2 mb-8">
        <div class="flex-grow">
          <input type="text" id="searchInput" placeholder="Search hospitals, specialties, or treatments..." class="w-full px-4 py-3 text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <button id="searchButton" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">Search</button>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Filters -->
    <div class="mb-8 flex flex-wrap gap-4">
      <select id="specialtyFilter" class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <option value="">All Specialties</option>
        <option value="Cardiology">Cardiology</option>
        <option value="Orthopedics">Orthopedics</option>
        <option value="Neurology">Neurology</option>
        <option value="Oncology">Oncology</option>
        <option value="Pediatrics">Pediatrics</option>
      </select>
      <select id="availabilityFilter" class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <option value="">Availability</option>
        <option value="today">Today</option>
        <option value="tomorrow">Tomorrow</option>
        <option value="this_week">This Week</option>
      </select>
      <select id="ratingFilter" class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <option value="">Rating: Any</option>
        <option value="4">4+ Stars</option>
        <option value="3">3+ Stars</option>
      </select>
    </div>

    <!-- Hospital Listings -->
    <div id="hospitalList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($hospitals as $hospital): ?>
        <div class="hospital-card bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300" data-hospital-id="<?php echo $hospital['id']; ?>">
          <div class="h-48 overflow-hidden">
            <?php 
              $image_path = isset($hospital_images[$hospital['id']]) 
                ? $hospital_images[$hospital['id']] 
                : 'https://via.placeholder.com/500?text=No+Image+ID' . $hospital['id']; 
            ?>
            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($hospital['name']); ?>" class="w-full h-full object-cover">
          </div>
          <div class="p-6">
            <div class="flex justify-between items-start">
              <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($hospital['name']); ?></h3>
              <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded"><?php echo $hospital['rating']; ?> ★</span>
            </div>
            <div class="text-gray-500 mb-4 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1">
                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
              <span><?php echo htmlspecialchars($hospital['address']); ?></span>
            </div>
            <div class="mb-4">
              <?php foreach (explode(',', $hospital['specialties']) as $specialty): ?>
                <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded mr-2 mb-2"><?php echo htmlspecialchars($specialty); ?></span>
              <?php endforeach; ?>
              <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="inline mr-1">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <?php echo $hospital['slots_available']; ?> slots available
              </span>
            </div>
            <button class="book-appointment w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">Book Appointment</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Additional Features -->
    <div class="mt-12">
      <h2 class="text-2xl font-bold mb-6">Hospital Services</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-2">Bed Availability</h3>
          <p class="text-gray-600 mb-4">Find available beds across wards and ICUs.</p>
          <a href="beds.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 inline-block">Check Beds</a>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-2">Inventory Status</h3>
          <p class="text-gray-600 mb-4">View available medicines and consumables.</p>
          <a href="inventory.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 inline-block">View Inventory</a>
        </div>
      </div>
    </div>
  </main>

  <!-- Booking Modal -->
  <div id="bookingModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg p-8 max-w-md w-full">
      <div class="flex justify-between items-start mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Book Appointment</h2>
        <button id="closeModal" class="text-gray-400 hover:text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <form id="appointmentForm" class="space-y-4">
        <input type="hidden" id="hospitalId">
        <div>
          <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department/Specialty</label>
          <select id="department" name="department" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">Select a department</option>
          </select>
        </div>
        <div>
          <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Preferred Date</label>
          <input type="date" id="date" name="date" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
          <label for="time" class="block text-sm font-medium text-gray-700 mb-1">Preferred Time</label>
          <select id="time" name="time" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">Select a time slot</option>
            <option value="morning">Morning (9 AM - 12 PM)</option>
            <option value="afternoon">Afternoon (1 PM - 4 PM)</option>
            <option value="evening">Evening (5 PM - 8 PM)</option>
          </select>
        </div>
        <div>
          <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for Visit</label>
          <textarea id="reason" name="reason" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Briefly describe your symptoms or reason for appointment"></textarea>
        </div>
        <div class="flex items-start">
          <input id="terms" name="terms" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mt-1">
          <label for="terms" class="ml-2 block text-sm text-gray-700">
            I agree to the <a href="#" class="text-blue-600 hover:underline">terms and conditions</a>.
          </label>
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">Confirm Appointment</button>
      </form>
    </div>
  </div>

  <!-- JavaScript -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Hospital data from PHP
      const hospitals = <?php echo json_encode($hospitals); ?>;
      const hospitalImages = <?php echo json_encode($hospital_images); ?>;
      const hospitalList = document.getElementById('hospitalList');
      const specialtyFilter = document.getElementById('specialtyFilter');
      const availabilityFilter = document.getElementById('availabilityFilter');
      const ratingFilter = document.getElementById('ratingFilter');
      const searchInput = document.getElementById('searchInput');
      const searchButton = document.getElementById('searchButton');
      const bookingModal = document.getElementById('bookingModal');
      const closeModal = document.getElementById('closeModal');
      const appointmentForm = document.getElementById('appointmentForm');
      const departmentSelect = document.getElementById('department');
      const logoutButton = document.getElementById('logout-button');
      const profileButton = document.getElementById('profile-button');
      const profileDropdown = document.getElementById('profile-dropdown');

      // Function to render hospitals
      function renderHospitals(filteredHospitals) {
        hospitalList.innerHTML = '';
        if (filteredHospitals.length === 0) {
          hospitalList.innerHTML = '<p class="text-gray-600 col-span-3">No hospitals match your criteria.</p>';
          return;
        }
        filteredHospitals.forEach(hospital => {
          const specialties = hospital.specialties.split(',');
          const imagePath = hospitalImages[hospital.id] || `https://via.placeholder.com/500?text=No+Image+ID${hospital.id}`;
          const card = document.createElement('div');
          card.className = 'hospital-card bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300';
          card.dataset.hospitalId = hospital.id;
          card.innerHTML = `
            <div class="h-48 overflow-hidden">
              <img src="${imagePath}" alt="${hospital.name}" class="w-full h-full object-cover">
            </div>
            <div class="p-6">
              <div class="flex justify-between items-start">
                <h3 class="text-xl font-bold text-gray-900 mb-2">${hospital.name}</h3>
                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">${hospital.rating} ★</span>
              </div>
              <div class="text-gray-500 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1">
                  <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                  <circle cx="12" cy="10" r="3"></circle>
                </svg>
                <span>${hospital.address}</span>
              </div>
              <div class="mb-4">
                ${specialties.map(s => `<span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded mr-2 mb-2">${s}</span>`).join('')}
                <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded mb-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="inline mr-1">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                  </svg>
                  ${hospital.slots_available} slots available
                </span>
              </div>
              <button class="book-appointment w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">Book Appointment</button>
            </div>
          `;
          hospitalList.appendChild(card);
        });
        // Re-attach booking button events
        attachBookingEvents();
      }

      // Function to filter hospitals
      function filterHospitals() {
        const specialty = specialtyFilter.value;
        const availability = availabilityFilter.value;
        const rating = ratingFilter.value;
        const searchTerm = searchInput.value.trim().toLowerCase();

        let filteredHospitals = hospitals;

        // Search filter
        if (searchTerm) {
          filteredHospitals = filteredHospitals.filter(hospital => 
            hospital.name.toLowerCase().includes(searchTerm) ||
            hospital.specialties.toLowerCase().split(',').some(s => s.trim().toLowerCase().includes(searchTerm))
          );
        }

        // Specialty filter
        if (specialty) {
          filteredHospitals = filteredHospitals.filter(hospital => 
            hospital.specialties.split(',').map(s => s.trim()).includes(specialty)
          );
        }

        // Availability filter
        if (availability) {
          filteredHospitals = filteredHospitals.filter(hospital => {
            const slots = parseInt(hospital.slots_available);
            if (availability === 'today') return slots > 0;
            if (availability === 'tomorrow') return slots > 2;
            if (availability === 'this_week') return slots >= 5;
            return true;
          });
        }

        // Rating filter
        if (rating) {
          filteredHospitals = filteredHospitals.filter(hospital => 
            parseFloat(hospital.rating) >= parseFloat(rating)
          );
        }

        renderHospitals(filteredHospitals);
      }

      // Attach filter and search events
      specialtyFilter.addEventListener('change', filterHospitals);
      availabilityFilter.addEventListener('change', filterHospitals);
      ratingFilter.addEventListener('change', filterHospitals);
      searchInput.addEventListener('input', filterHospitals);
      searchButton.addEventListener('click', filterHospitals);

      // Function to populate department dropdown
      function populateDepartments(hospitalId) {
        const hospital = hospitals.find(h => h.id == hospitalId);
        departmentSelect.innerHTML = '<option value="">Select a department</option>';
        if (hospital && hospital.specialties) {
          const isMultiSpecialty = hospital.specialties.trim().toLowerCase() === 'multi-specialty';
          const specialties = isMultiSpecialty 
            ? ['Cardiology', 'Neurology', 'Orthopedics', 'Pediatrics', 'Oncology']
            : hospital.specialties.split(',').map(s => s.trim());
          specialties.forEach(specialty => {
            const option = document.createElement('option');
            option.value = specialty.toLowerCase();
            option.textContent = specialty;
            departmentSelect.appendChild(option);
          });
        }
      }

      // Function to attach booking button events
      function attachBookingEvents() {
        const bookingButtons = document.querySelectorAll('.book-appointment');
        bookingButtons.forEach(button => {
          button.addEventListener('click', function() {
            <?php if (!isset($_SESSION['user_id'])): ?>
              window.location.href = 'login.php';
              return;
            <?php endif; ?>
            const hospitalId = this.closest('.hospital-card').dataset.hospitalId;
            document.getElementById('hospitalId').value = hospitalId;
            populateDepartments(hospitalId);
            bookingModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
          });
        });
      }

      // Toggle profile dropdown
      if (profileButton) {
        profileButton.addEventListener('click', (e) => {
          e.stopPropagation();
          profileDropdown.classList.toggle('hidden');
        });
      }

      document.addEventListener('click', (e) => {
        if (profileDropdown && !profileDropdown.classList.contains('hidden') && !profileButton.contains(e.target)) {
          profileDropdown.classList.add('hidden');
        }
      });

      // Logout
      if (logoutButton) {
        logoutButton.addEventListener('click', () => {
          fetch('php/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=logout'
          })
            .then(response => response.json())
            .then(data => {
              if (data.success) window.location.href = data.redirect;
            });
        });
      }

      // Booking modal
      attachBookingEvents();

      closeModal.addEventListener('click', () => {
        bookingModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
      });

      bookingModal.addEventListener('click', (e) => {
        if (e.target === bookingModal) {
          bookingModal.classList.add('hidden');
          document.body.style.overflow = 'auto';
        }
      });

      // Appointment form submission
      appointmentForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(appointmentForm);
        formData.append('hospital_id', document.getElementById('hospitalId').value);

        if (!document.getElementById('terms').checked) {
          alert('Please accept the terms and conditions.');
          return;
        }

        fetch('php/appointment.php', {
          method: 'POST',
          body: formData
        })
          .then(response => {
            if (!response.ok) {
              throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
          })
          .then(data => {
            alert(data.message);
            if (data.success) {
              bookingModal.classList.add('hidden');
              document.body.style.overflow = 'auto';
              appointmentForm.reset();
            }
          })
          .catch(error => {
            alert('Error booking appointment: ' + error.message);
          });
      });
    });
  </script>
</body>
</html>