const activityForm = document.getElementById("activityForm");
const dateInput = document.getElementById("date");
const timeInput = document.getElementById("time");
const activityInput = document.getElementById("activity");
const categoryInput = document.getElementById("category");
const durationInput = document.getElementById("duration");
const effortLevelInput = document.getElementById("effortLevel");
const noteInput = document.getElementById("activityNote");
const submitButton = document.getElementById("saveActivityBtn");
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
    submitButton.textContent = isSubmitting ? "Saving..." : "Save Activity";
}

function validateForm(data) {
    if (!data.date) {
        return "Please select a date.";
    }

    if (!data.time) {
        return "Please select a time.";
    }

    if (!data.activity_name) {
        return "Please choose an activity.";
    }

    if (data.duration_minutes !== null && (data.duration_minutes < 1 || data.duration_minutes > 300)) {
        return "Please enter a valid duration between 1 and 300 minutes.";
    }

    if (data.effort_level !== null && (data.effort_level < 1 || data.effort_level > 5)) {
        return "Please select a valid effort level.";
    }

    return null;
}

function buildPayload() {
    return {
        date: dateInput.value,
        time: timeInput.value,
        activity_name: activityInput.value,
        category: categoryInput.value || null,
        duration_minutes: durationInput.value ? Number(durationInput.value) : null,
        effort_level: effortLevelInput.value ? Number(effortLevelInput.value) : null,
        note: noteInput.value.trim() || null
    };
}

function resetFormFields() {
    activityInput.value = "";
    categoryInput.value = "";
    durationInput.value = "";
    effortLevelInput.value = "";
    noteInput.value = "";
    setDefaultDateTime();
}

async function submitActivityEntry(event) {
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

        const response = await fetch("/api/activities", {
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
            let errorMessage = result.message || "Failed to save activity.";

            if (result.errors && typeof result.errors === "object") {
                errorMessage += " " + Object.values(result.errors).join(" ");
            }

            showMessage(errorMessage, "error");
            return;
        }

        showMessage("Activity saved successfully.", "success");
        resetFormFields();
    } catch (error) {
        console.error("Activity save error:", error);
        showMessage("Could not connect to the backend.", "error");
    } finally {
        setSubmitting(false);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    setDefaultDateTime();
    activityForm.addEventListener("submit", submitActivityEntry);
});