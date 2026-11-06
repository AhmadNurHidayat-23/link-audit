document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", (event) => {
        if (!form.checkValidity()) return;
        const button = event.submitter;
        if (!(button instanceof HTMLButtonElement)) return;
        if (button.name) {
            const hidden = document.createElement("input");
            hidden.type = "hidden";
            hidden.name = button.name;
            hidden.value = button.value;
            form.appendChild(hidden);
        }
        button.disabled = true;
        button.dataset.original = button.textContent;
        button.textContent = "Memproses…";
    });
});
window.addEventListener("pageshow", () => {
    document.querySelectorAll("button[data-original]").forEach((button) => {
        button.disabled = false;
        button.textContent = button.dataset.original;
    });
});
