document.getElementById('activityForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const getCheckedValues = (name) =>
        Array.from(document.querySelectorAll(`input[name="${name}"]:checked`))
            .map(el => parseInt(el.value));

    const otherParts = [
        document.getElementById('emotion_other').value.trim()       && 'Other emotion: '        + document.getElementById('emotion_other').value.trim(),
        document.getElementById('stress_factor_other').value.trim() && 'Other stress factor: '  + document.getElementById('stress_factor_other').value.trim(),
        document.getElementById('activity_other').value.trim()      && 'Other activity: '       + document.getElementById('activity_other').value.trim(),
    ].filter(Boolean);

    const baseNote = document.getElementById('note').value.trim();
    const note = [baseNote, ...otherParts].filter(Boolean).join('\n') || null;

    const payload = {
        date: document.getElementById('date').value,
        time: document.getElementById('time').value,
        intensity: parseInt(document.getElementById('intensity').value),
        stress_level: parseInt(document.getElementById('stress_level').value),
        mood_level: parseInt(document.getElementById('mood_level').value),
        note,
        emotion_ids: getCheckedValues('emotion_ids'),
        stress_factor_ids: getCheckedValues('stress_factor_ids'),
        activity_ids: getCheckedValues('activity_ids'),
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
