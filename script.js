document.getElementById('send-btn').addEventListener('click', function() {
    const input = document.querySelector('input').value;
    if (input) {
        alert("Searching for: " + input + "\n(This is where we will link the fitness AI logic!)");
    }
});

// Allow 'Enter' key to trigger search
document.querySelector('input').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
        document.getElementById('send-btn').click();
    }
});

// New Chat button functionality
document.querySelector('.new-chat-btn')?.addEventListener('click', function() {
    document.querySelector('input').value = '';
    document.querySelector('input').focus();
});

// History item click
document.querySelectorAll('.history-item').forEach(item => {
    item.addEventListener('click', function() {
        document.querySelectorAll('.history-item').forEach(i => i.classList.remove('active'));
        this.classList.add('active');
    });
});

// Settings button
document.querySelector('.settings-btn')?.addEventListener('click', function() {
    const modal = document.getElementById('settings-modal');
    modal.style.display = 'flex';
});

// Close settings modal
document.querySelector('.close-modal')?.addEventListener('click', function() {
    const modal = document.getElementById('settings-modal');
    modal.style.display = 'none';
});

// Close modal when clicking outside
document.getElementById('settings-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});

// Theme toggle
document.getElementById('dark-mode-btn')?.addEventListener('click', function() {
    document.body.className = 'dark-mode';
    document.querySelectorAll('.theme-btn').forEach(btn => btn.classList.remove('active'));
    this.classList.add('active');
    localStorage.setItem('theme', 'dark-mode');
});

document.getElementById('light-mode-btn')?.addEventListener('click', function() {
    document.body.className = 'light-mode';
    document.querySelectorAll('.theme-btn').forEach(btn => btn.classList.remove('active'));
    this.classList.add('active');
    localStorage.setItem('theme', 'light-mode');
});

// Load saved theme
const savedTheme = localStorage.getItem('theme') || 'dark-mode';
document.body.className = savedTheme;
if (savedTheme === 'dark-mode') {
    document.getElementById('dark-mode-btn')?.classList.add('active');
} else {
    document.getElementById('light-mode-btn')?.classList.add('active');
}

// Collapse button in sidebar
document.getElementById('menu-toggle-sidebar')?.addEventListener('click', function() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('collapsed');
});