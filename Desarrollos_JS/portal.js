const portalShell = document.getElementById("portal-shell");
const sidebarToggle = document.getElementById("sidebar-toggle");

if (portalShell && sidebarToggle) {
    let collapsed = false;
    try {
        collapsed = localStorage.getItem("smartBusinessSidebarCollapsed") === "1";
    } catch (error) {
        console.warn("No se pudo recuperar el estado del menú lateral.", error);
    }

    const clientsSearch = document.getElementById("clients-search");
    const clientsSearchInput = document.getElementById("clients-search-input");
    const clientsSearchStatus = document.getElementById("clients-search-status");
    const clientsTable = document.getElementById("clients-active-table");

    if (clientsSearch && clientsSearchInput && clientsSearchStatus && clientsTable) {
        const normalizeSearch = (value) => value.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();
        const rows = Array.from(clientsTable.querySelectorAll("tbody tr")).map((row) => ({
            element: row,
            fields: [row.dataset.name, row.dataset.ruc, row.dataset.certificate].map(normalizeSearch),
        }));

        const filterClients = () => {
            const query = normalizeSearch(clientsSearchInput.value);
            let matches = 0;
            rows.forEach(({ element, fields }) => {
                const visible = fields.some((field) => field.includes(query));
                element.hidden = !visible;
                if (visible) matches += 1;
            });
            clientsSearchStatus.textContent = matches === 0
                ? "No se encontraron clientes."
                : `${matches} ${matches === 1 ? "cliente encontrado" : "clientes encontrados"}.`;
        };

        clientsSearchInput.addEventListener("input", filterClients);
        clientsSearch.hidden = false;
        filterClients();
    }

    document.querySelectorAll("form[data-confirm]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

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
