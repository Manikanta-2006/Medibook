<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Page</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .animate-fadeIn { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .hospital-bg { background-image: url('https://images.unsplash.com/photo-1487958449943-2429e8be8625?auto=format&fit=crop&q=80'); background-size: cover; background-position: center; }
  </style>
</head>
<body class="min-h-screen bg-gray-50">
  <div class="min-h-screen flex flex-col md:flex-row">
    <div class="flex-1 flex flex-col items-center justify-center p-8 md:p-16">
      <div class="w-full max-w-md">
        <div class="mb-8 text-center md:text-left">
          <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Welcome</h2>
          <p class="mt-2 text-gray-600">Please sign in to your account</p>
        </div>
        <form id="loginForm" class="space-y-6 w-full max-w-md">
          <div class="space-y-1">
            <input id="email" type="email" placeholder="Email address" class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all" required>
            <p id="emailError" class="text-sm text-red-500 mt-1 hidden"></p>
          </div>
          <div class="space-y-1">
            <div class="relative">
              <input id="password" type="password" placeholder="Password" class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all" required>
              <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Show password">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon">
                  <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-off-icon hidden">
                  <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                  <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                  <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                  <line x1="2" x2="22" y1="2" y2="22"></line>
                </svg>
              </button>
            </div>
            <p id="passwordError" class="text-sm text-red-500 mt-1 hidden"></p>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input id="remember-me" type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
              <label for="remember-me" class="ml-2 text-sm text-gray-600 cursor-pointer">Remember me</label>
            </div>
            <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-500 transition-colors">Forgot password?</a>
          </div>
          <button type="submit" id="submitButton" class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 active:bg-blue-800 transition-all">Sign In</button>
          <div class="text-center text-sm text-gray-500">
            Don't have an account? <a href="signup.php" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">Sign up</a>
          </div>
        </form>
      </div>
    </div>
    <div class="hidden md:block flex-1 hospital-bg p-12 relative overflow-hidden">
      <div class="absolute inset-0 bg-blue-500/30 backdrop-blur-sm"></div>
      <div class="relative h-full flex flex-col justify-center items-center text-white z-10">
        <h3 class="text-3xl font-bold mb-4 text-center">MediBook</h3>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('loginForm');
      const emailInput = document.getElementById('email');
      const passwordInput = document.getElementById('password');
      const emailError = document.getElementById('emailError');
      const passwordError = document.getElementById('passwordError');
      const togglePasswordButton = document.getElementById('togglePassword');
      const eyeIcon = document.querySelector('.eye-icon');
      const eyeOffIcon = document.querySelector('.eye-off-icon');
      const submitButton = document.getElementById('submitButton');

      togglePasswordButton.addEventListener('click', function() {
        if (passwordInput.type === 'password') {
          passwordInput.type = 'text';
          eyeIcon.classList.add('hidden');
          eyeOffIcon.classList.remove('hidden');
          togglePasswordButton.setAttribute('aria-label', 'Hide password');
        } else {
          passwordInput.type = 'password';
          eyeIcon.classList.remove('hidden');
          eyeOffIcon.classList.add('hidden');
          togglePasswordButton.setAttribute('aria-label', 'Show password');
        }
      });

      form.addEventListener('submit', function(e) {
        e.preventDefault();
        emailError.classList.add('hidden');
        passwordError.classList.add('hidden');

        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('email', emailInput.value);
        formData.append('password', passwordInput.value);

        submitButton.textContent = 'Signing In...';
        submitButton.disabled = true;

        fetch('php/auth.php', {
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {
            submitButton.textContent = 'Sign In';
            submitButton.disabled = false;
            if (data.success) {
              window.location.href = data.redirect;
            } else {
              passwordError.textContent = data.message;
              passwordError.classList.remove('hidden');
            }
          });
      });
    });
  </script>
</body>
</html>