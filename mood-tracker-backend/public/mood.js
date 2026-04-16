const moodForm = document.getElementById("moodForm");
const dateInput = document.getElementById("date");
const timeInput = document.getElementById("time");
const stressInput = document.getElementById("stressLevel");
const moodLevelInput = document.getElementById("moodLevel");
const emotionsInput = document.getElementById("emotions");
const noteInput = document.getElementById("note");
const submitButton = document.getElementById("saveMoodBtn");
const messageEl = document.getElementById("message");

function pad(value) {
    return String(value).padStart(2, "0");
}

function getTodayDate() {
    const now = new Date();
    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

function getCurrentTime() {
    const now = new Date();
    return `${pad(now.getHours())}:${pad(now.getMinutes())}`;
}

function setDefaultDateTime() {
    if (!dateInput.value) {
        dateInput.value = getTodayDate();
    }

    if (!timeInput.value) {
        timeInput.value = getCurrentTime();
    }
}

function showMessage(text, type = "info") {
    messageEl.textContent = text;
    messageEl.style.color =
        type === "success" ? "#166534" :
        type === "error" ? "#b91c1c" :
        "#333";
}

function clearMessage() {
    messageEl.textContent = "";
    messageEl.style.color = "#333";
}

function setSubmitting(isSubmitting) {
    submitButton.disabled = isSubmitting;
    submitButton.textContent = isSubmitting ? "Saving..." : "Save Mood";
}

function validateForm(data) {
    if (!data.date) {
        return "Please select a date.";
    }

    if (!data.time) {
        return "Please select a time.";
    }

    if (!data.stress_level || data.stress_level < 1 || data.stress_level > 5) {
        return "Please select a valid stress level.";
    }

    if (!data.mood_level || data.mood_level < 1 || data.mood_level > 5) {
        return "Please select a valid mood level.";
    }

    if (!data.emotions) {
        return "Please select an emotion.";
    }

    return null;
}

function buildPayload() {
    return {
        date: dateInput.value,
        time: timeInput.value,
        stress_level: Number(stressInput.value),
        mood_level: Number(moodLevelInput.value),
        emotions: emotionsInput.value.trim(),
        note: noteInput.value.trim()
    };
}

function resetFormFields() {
    stressInput.value = "";
    moodLevelInput.value = "";
    emotionsInput.value = "";
    noteInput.value = "";
    setDefaultDateTime();
}

async function submitMoodEntry(event) {
    event.preventDefault();
    clearMessage();

    const payload = buildPayload();
    const validationError = validateForm(payload);

    if (validationError) {
        showMessage(validationError, "error");
        return;
    }

    try {
        setSubmitting(true);

        const response = await fetch("/api/moods", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        const rawText = await response.text();
        let result;

        try {
            result = JSON.parse(rawText);
        } catch (error) {
            showMessage("The server returned an invalid response.", "error");
            console.error("Invalid JSON response:", rawText);
            return;
        }

        if (!response.ok || !result.success) {
            let errorMessage = result.message || "Failed to save mood.";

            if (result.errors && typeof result.errors === "object") {
                errorMessage += " " + Object.values(result.errors).join(" ");
            }

            showMessage(errorMessage, "error");
            return;
        }

        showMessage("Mood saved successfully.", "success");
        resetFormFields();
    } catch (error) {
        console.error("Mood save error:", error);
        showMessage("Could not connect to the backend.", "error");
    } finally {
        setSubmitting(false);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    setDefaultDateTime();
    moodForm.addEventListener("submit", submitMoodEntry);
});