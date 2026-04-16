(function () {
    let reminderCheckIntervalStarted = false;
    let notificationPermissionRequested = false;

    async function requestNotificationPermissionSilently() {
        if (!("Notification" in window)) {
            return false;
        }

        if (Notification.permission === "granted") {
            return true;
        }

        if (Notification.permission === "denied") {
            return false;
        }

        if (notificationPermissionRequested) {
            return false;
        }

        notificationPermissionRequested = true;

        try {
            const permission = await Notification.requestPermission();
            return permission === "granted";
        } catch (error) {
            console.error("Notification permission error:", error);
            return false;
        }
    }

    async function fetchReminderJson(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                "Content-Type": "application/json",
                ...(options.headers || {})
            }
        });

        const rawText = await response.text();
        let result;

        try {
            result = JSON.parse(rawText);
        } catch (error) {
            throw new Error(`Invalid JSON response from ${url}`);
        }

        return {
            ok: response.ok,
            status: response.status,
            result
        };
    }

    async function deleteReminder(reminderId) {
        try {
            await fetchReminderJson(`/api/reminders/${reminderId}`, {
                method: "DELETE"
            });
        } catch (error) {
            console.error("Failed to delete reminder:", error);
        }
    }

    async function checkDueRemindersGlobal() {
        if (!("Notification" in window)) {
            return;
        }

        if (Notification.permission !== "granted") {
            return;
        }

        try {
            const { ok, status, result } = await fetchReminderJson("/api/reminders/due");

            if (status === 401) {
                return;
            }

            if (!ok || !result.success) {
                return;
            }

            const reminders = result.data?.reminders || [];

            for (const reminder of reminders) {
                try {
                    new Notification("Mood Tracker Reminder", {
                        body: reminder.reminder_message
                    });
                } catch (error) {
                    console.error("Notification display failed:", error);
                }

                await deleteReminder(reminder.reminder_id);
            }

            if (reminders.length > 0 && typeof window.loadSavedReminders === "function") {
                window.loadSavedReminders();
            }
        } catch (error) {
            console.error("Global reminder check failed:", error);
        }
    }

    async function startReminderChecks() {
        const hasPermission = await requestNotificationPermissionSilently();

        if (!hasPermission) {
            return;
        }

        await checkDueRemindersGlobal();

        if (!reminderCheckIntervalStarted) {
            reminderCheckIntervalStarted = true;
            setInterval(checkDueRemindersGlobal, 30000);
        }
    }

    document.addEventListener("DOMContentLoaded", startReminderChecks);

    window.checkDueRemindersGlobal = checkDueRemindersGlobal;
})();