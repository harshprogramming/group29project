document.getElementById('moodForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const getSelectedValues = (id) =>
        Array.from(document.getElementById(id).selectedOptions)
            .map(el => parseInt(el.value));

    const payload = {
        date: document.getElementById('date').value,
        time: document.getElementById('time').value,
        intensity: parseInt(document.getElementById('intensity').value),
        stress_level: parseInt(document.getElementById('stress_level').value),
        mood_level: parseInt(document.getElementById('mood_level').value),
        note: document.getElementById('note').value.trim() || null,
        emotion_ids: getSelectedValues('emotion_ids'),
        stress_factor_ids: getSelectedValues('stress_factor_ids'),
        activity_ids: getSelectedValues('activity_ids'),
    };

    try {
        const res = await fetch('http://localhost:8000/api/moods', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload),
        });

        const text = await res.text();
        let json;
        try {
            json = JSON.parse(text);
        } catch {
            alert('Server error (not JSON):\n' + text.replace(/<[^>]+>/g, '').trim().slice(0, 300));
            return;
        }

        if (!res.ok) {
            alert('Error: ' + (json.message || 'Something went wrong'));
            return;
        }

        alert('Mood entry saved!');
        e.target.reset();
    } catch (err) {
        alert('Network error: ' + err.message);
    }
});
