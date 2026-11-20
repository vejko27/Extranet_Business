const portalShell = document.getElementById("portal-shell");
const sidebarToggle = document.getElementById("sidebar-toggle");

if (portalShell && sidebarToggle) {
    let collapsed = false;
    try {
        collapsed = localStorage.getItem("smartBusinessSidebarCollapsed") === "1";
    } catch (error) {
        console.warn("No se pudo recuperar el estado del menú lateral.", error);
    }

    const updateSidebar = () => {
        portalShell.classList.toggle("sidebar-collapsed", collapsed);
        sidebarToggle.setAttribute("aria-pressed", String(collapsed));
        const label = collapsed ? "Expandir menú lateral" : "Reducir menú lateral";
        sidebarToggle.setAttribute("aria-label", label);
        sidebarToggle.title = label;
    };

    updateSidebar();
    sidebarToggle.hidden = false;
    sidebarToggle.addEventListener("click", () => {
        collapsed = !collapsed;
        updateSidebar();
        try {
            localStorage.setItem("smartBusinessSidebarCollapsed", collapsed ? "1" : "0");
        } catch (error) {
            console.warn("No se pudo guardar el estado del menú lateral.", error);
        }
    });
}
