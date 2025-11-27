<?php
session_start();
require 'php/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    $stmt = $pdo->prepare("
        SELECT a.*, h.name AS hospital_name
        FROM appointments a
        JOIN hospitals h ON a.hospital_id = h.id
        WHERE a.user_id = ?
        ORDER BY a.appointment_date
    ");
    $stmt->execute([$user_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $appointments = [];
    $error = "Error fetching appointments: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .animate-fadeIn { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .profile-bg { background-image: url('https://images.unsplash.com/photo-1649972904349-6e44c42644a7?auto=format&fit=crop&q=80'); background-size: cover; background-position: center; }
  </style>
</head>
<body class="min-h-screen bg-gray-100">
  <header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16 items-center">
        <div class="flex">
          <a href="home.php" class="text-lg font-bold text-blue-600">MediBook</a>
        </div>
        <nav class="flex space-x-4 items-center">
          <a href="home.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 text-sm font-medium">Hospitals</a>
          <a href="profile.php" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2 text-sm font-medium">My Profile</a>
          <button id="logoutButton" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-md text-sm font-medium transition-colors">Logout</button>
        </nav>
      </div>
    </div>
  </header>

  <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 sm:px-0">
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="profile-bg h-40 relative">
          <div class="absolute inset-0 bg-blue-500/30 backdrop-blur-sm"></div>
        </div>
        <div class="px-4 py-5 sm:p-6 -mt-16 relative">
          <div class="sm:flex sm:items-center sm:justify-between">
            <div class="sm:flex sm:items-center">
              <div class="mb-4 sm:mb-0 sm:mr-4 flex-shrink-0">
                <div id="userAvatar" class="h-24 w-24 rounded-full bg-blue-100 border-4 border-white flex items-center justify-center text-blue-500 text-3xl font-bold">
                  <?php echo htmlspecialchars(substr($user['full_name'], 0, 1)); ?>
                </div>
              </div>
              <div>
                <h1 id="username" class="text-2xl font-bold text-gray-900 sm:text-3xl"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <p id="userEmail" class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
              </div>
            </div>
          </div>
        </div>
        <div class="border-t border-gray-200">
          <div class="px-4 py-5 sm:p-6">
            <h2 class="text-lg font-medium text-gray-900">Your Appointments</h2>
            <?php if (isset($error)): ?>
              <p class="text-red-600"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <div id="appointmentsContainer" class="mt-4 space-y-4">
              <?php if (empty($appointments)): ?>
                <p id="noAppointments" class="text-gray-500 italic">No appointments found.</p>
              <?php else: ?>
                <?php foreach ($appointments as $appt): ?>
                  <div class="border border-gray-200 rounded-md p-4 animate-fadeIn">
                    <div class="flex justify-between">
                      <div>
                        <h3 class="font-medium"><?php echo htmlspecialchars($appt['hospital_name']); ?></h3>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($appt['department']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($appt['appointment_date'] . ' at ' . $appt['time_slot']); ?></p>
                        <p class="text-sm text-gray-500">Reason: <?php echo htmlspecialchars($appt['reason'] ?? 'Not specified'); ?></p>
                        <p class="text-sm text-gray-500">Status: <?php echo htmlspecialchars($appt['status']); ?></p>
                      </div>
                      <div>
                        <button data-appt-id="<?php echo $appt['id']; ?>" class="cancel-appt text-red-600 hover:text-red-800 text-sm">Cancel</button>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <div class="mt-8">
              <a href="home.php" class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">Book New Appointment</a>
            </div>
          </div>
        </div>
        <div class="border-t border-gray-200">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center">
              <h2 class="text-lg font-medium text-gray-900">Personal Information</h2>
              <button id="editInfoButton" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">Edit Info</button>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-4">
              <div>
                <h3 class="text-sm font-medium text-gray-500">Name</h3>
                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-500">Email</h3>
                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-500">Phone Number</h3>
                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-500">Address</h3>
                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?></p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-500">Date of Birth</h3>
                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['dob'] ?? 'Not provided'); ?></p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-500">Gender</h3>
                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['gender'] ?? 'Not provided'); ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Edit Info Modal -->
  <div id="editInfoModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg p-8 max-w-md w-full">
      <div class="flex justify-between items-start mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Edit Personal Information</h2>
        <button id="closeEditModal" class="text-gray-400 hover:text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <form id="editInfoForm" class="space-y-4">
        <div>
          <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
          <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
          <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="10 digits" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
          <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label for="dob" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
          <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
          <select id="gender" name="gender" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Select Gender</option>
            <option value="Male" <?php echo $user['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
            <option value="Female" <?php echo $user['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
            <option value="Other" <?php echo $user['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
          </select>
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">Save Changes</button>
      </form>
    </div>
  </div>

  <footer class="bg-white border-t border-gray-200 mt-12">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <p class="text-center text-sm text-gray-500">© 2025 MediBook. All rights reserved.</p>
    </div>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Logout
      document.getElementById('logoutButton').addEventListener('click', function() {
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

      // Cancel Appointment
      document.querySelectorAll('.cancel-appt').forEach(button => {
        button.addEventListener('click', function() {
          const apptId = this.dataset.apptId;
          if (confirm('Are you sure you want to cancel this appointment?')) {
            fetch('php/cancel_appointment.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: `appt_id=${apptId}`
            })
              .then(response => response.json())
              .then(data => {
                alert(data.message);
                if (data.success) {
                  this.closest('.border').remove();
                  if (!document.querySelector('.cancel-appt')) {
                    document.getElementById('appointmentsContainer').innerHTML = '<p id="noAppointments" class="text-gray-500 italic">No appointments found.</p>';
                  }
                }
              })
              .catch(error => {
                alert('Error cancelling appointment: ' + error.message);
              });
          }
        });
      });

      // Edit Info Modal
      const editInfoModal = document.getElementById('editInfoModal');
      const editInfoButton = document.getElementById('editInfoButton');
      const closeEditModal = document.getElementById('closeEditModal');
      const editInfoForm = document.getElementById('editInfoForm');

      editInfoButton.addEventListener('click', () => {
        editInfoModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
      });

      closeEditModal.addEventListener('click', () => {
        editInfoModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
      });

      editInfoModal.addEventListener('click', (e) => {
        if (e.target === editInfoModal) {
          editInfoModal.classList.add('hidden');
          document.body.style.overflow = 'auto';
        }
      });

      editInfoForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(editInfoForm);

        fetch('php/update_profile.php', {
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {
            alert(data.message);
            if (data.success) {
              editInfoModal.classList.add('hidden');
              document.body.style.overflow = 'auto';
              window.location.reload(); // Refresh to show updated info
            }
          })
          .catch(error => {
            alert('Error updating profile: ' + error.message);
          });
      });
    });
  </script>
</body>
</html>