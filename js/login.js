document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const errorEl = document.getElementById('loginError');
    errorEl.textContent = '';

    const payload = {
        email: document.getElementById('email').value.trim(),
        password: document.getElementById('password').value,
    };

    try {
        const res = await fetch('http://localhost:8000/api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload),
        });

        const json = await res.json();

        if (!res.ok) {
            errorEl.textContent = json.message || 'Login failed. Please try again.';
            return;
        }

        window.location.href = 'home.html';
    } catch (err) {
        errorEl.textContent = 'Network error: ' + err.message;
    }
});
