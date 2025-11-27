<?php
session_start();
require 'php/config.php';

$stmt = $pdo->query("
    SELECT i.item_name, i.type, i.quantity, h.name AS hospital_name
    FROM inventory i
    JOIN hospitals h ON i.hospital_id = h.id
    ORDER BY i.item_name
");
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory Status - MediBook</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .animate-fadeIn { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
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
        <a href="home.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Back to Home</a>
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

  <!-- Main Content -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Inventory Status</h2>
    <?php if (empty($inventory)): ?>
      <p class="text-gray-600">No inventory found.</p>
    <?php else: ?>
      <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hospital</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($inventory as $item): ?>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($item['type']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($item['hospital_name']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($item['quantity']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>

  <!-- JavaScript -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const logoutButton = document.getElementById('logout-button');
      const profileButton = document.getElementById('profile-button');
      const profileDropdown = document.getElementById('profile-dropdown');

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
    });
  </script>
</body>
</html>