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

export function bootGoogleCalendars(root = document) {
    root.querySelectorAll("[data-google-calendar]").forEach((el) => initGoogleCalendar(el));
}
