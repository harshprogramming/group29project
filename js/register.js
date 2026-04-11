document.getElementById('registerForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const errorEl = document.getElementById('registerError');
    errorEl.textContent = '';

    const payload = {
        name: document.getElementById('name').value.trim(),
        email: document.getElementById('email').value.trim(),
        password: document.getElementById('password').value,
        phone: document.getElementById('phone').value.trim() || null,
        age: document.getElementById('age').value ? parseInt(document.getElementById('age').value) : null,
        gender: document.getElementById('gender').value || null,
    };

    try {
        const res = await fetch('http://localhost:8000/api/auth/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload),
        });

        const json = await res.json();

        if (!res.ok) {
            errorEl.textContent = json.message || 'Registration failed. Please try again.';
            return;
        }

        window.location.href = 'login.html';
    } catch (err) {
        errorEl.textContent = 'Network error: ' + err.message;
    }
});
