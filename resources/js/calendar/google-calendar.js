import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";

function buildEventsUrl(baseUrl, info) {
    const url = new URL(baseUrl, window.location.origin);
    url.searchParams.set("start", info.startStr);
    url.searchParams.set("end", info.endStr);
    return url.toString();
}

export function initGoogleCalendar(el) {
    if (!el) return;

    const eventsUrl = el.dataset.eventsUrl;
    if (!eventsUrl) return;

    if (el.__fullcalendar) {
        el.__fullcalendar.destroy();
        el.__fullcalendar = null;
    }

    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: "dayGridMonth",
        height: "auto",
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay",
        },
        navLinks: true,
        selectable: true,
        selectMirror: true,
        eventTimeFormat: {
            hour: "2-digit",
            minute: "2-digit",
            meridiem: false,
        },
        select: (info) => {
            try {
                if (window.Livewire && typeof window.Livewire.dispatch === "function") {
                    window.Livewire.dispatch("calendar-range-selected", {
                        start: info.startStr,
                        end: info.endStr,
                        allDay: !!info.allDay,
                    });
                }
            } finally {
                calendar.unselect();
            }
        },
        dateClick: (info) => {
            if (window.Livewire && typeof window.Livewire.dispatch === "function") {
                // Provide end as start; the server will default duration.
                window.Livewire.dispatch("calendar-range-selected", {
                    start: info.dateStr,
                    end: info.dateStr,
                    allDay: !!info.allDay,
                });
            }
        },
        eventClick: (info) => {
            const eventId = info?.event?.id;
            if (!eventId) return;

            if (window.Livewire && typeof window.Livewire.dispatch === "function") {
                window.Livewire.dispatch("calendar-event-clicked", {
                    eventId,
                });
            }
        },
        events: async (info, successCallback, failureCallback) => {
            try {
                const res = await fetch(buildEventsUrl(eventsUrl, info), {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });

                if (!res.ok) {
                    failureCallback(new Error(`Failed to load events (${res.status})`));
                    return;
                }

                const data = await res.json();
                successCallback(data);
            } catch (e) {
                failureCallback(e);
            }
        },
    });

    calendar.render();
    el.__fullcalendar = calendar;

    // Listen for server-side requests to refetch events.
    if (!el.__livewireCalendarListenersBound) {
        el.__livewireCalendarListenersBound = true;

        const bind = () => {
            if (window.Livewire && typeof window.Livewire.on === "function") {
                window.Livewire.on("calendar-refetch", () => {
                    try {
                        el.__fullcalendar?.refetchEvents();
                    } catch {
                        // ignore
                    }
                });
            }
        };

        // If Livewire is already initialized, bind immediately.
        bind();
        // Also bind when Livewire initializes (covers edge cases).
        document.addEventListener("livewire:init", bind, { once: true });
    }
}

function showCalendarBrowserNotification(notification) {
    if (!("Notification" in window) || Notification.permission !== "granted") {
        return;
    }

    const browserNotification = new Notification(notification.title, {
        body: notification.message,
        tag: `calendar-notification-${notification.id}`,
    });

    browserNotification.onclick = () => {
        window.focus();
        browserNotification.close();
    };
}

function initCalendarNotifications(el) {
    if (!el || el.__calendarNotificationsBooted) return;

    const checkUrl = el.dataset.checkUrl;
    if (!checkUrl) return;

    el.__calendarNotificationsBooted = true;
    el.__lastCalendarNotificationCheck = new Date().toISOString();

    if (window.__calendarNotificationInterval) {
        window.clearInterval(window.__calendarNotificationInterval);
    }

    const checkNotifications = async () => {
        try {
            const res = await fetch(checkUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({
                    last_check: el.__lastCalendarNotificationCheck,
                }),
            });

            if (!res.ok) return;

            const data = await res.json();
            (data.notifications || [])
                .slice()
                .reverse()
                .forEach((notification) =>
                    showCalendarBrowserNotification(notification)
                );

            el.__lastCalendarNotificationCheck =
                data.checked_at || new Date().toISOString();
        } catch {
            // ignore
        }
    };

    const requestPermissionButton = el.querySelector(
        "[data-enable-calendar-notifications]"
    );
    if (requestPermissionButton && "Notification" in window) {
        requestPermissionButton.addEventListener("click", async () => {
            try {
                await Notification.requestPermission();
            } catch {
                // ignore
            }
        });
    }

    setTimeout(checkNotifications, 2000);
    window.__calendarNotificationInterval = window.setInterval(
        checkNotifications,
        30000
    );
}

export function bootGoogleCalendars(root = document) {
    root.querySelectorAll("[data-google-calendar]").forEach((el) => initGoogleCalendar(el));
    root.querySelectorAll("[data-calendar-notification-feed]").forEach((el) => initCalendarNotifications(el));
}
