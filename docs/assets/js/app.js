/* ====================================================================
 * TaskFlow - Demo estática (client-side) para GitHub Pages.
 *
 * Réplica de la aplicación PHP, pero ejecutándose por completo en el
 * navegador. Los datos (usuarios, tareas y sesión) se guardan en
 * localStorage, por lo que NO se comparten entre dispositivos ni entre
 * visitantes: es una demostración de la interfaz y el flujo, no la
 * versión multiusuario real con backend.
 * ==================================================================== */

(function () {
    "use strict";

    /* ---------- Catálogo de estados (igual que en la app PHP) ---------- */
    const STATUSES = {
        pendiente:   "Pendiente",
        en_progreso: "En progreso",
        completada:  "Completada",
    };
    const STATUS_ORDER = { pendiente: 0, en_progreso: 1, completada: 2 };

    function badgeClass(status) {
        return {
            pendiente:   "bg-secondary",
            en_progreso: "bg-warning text-dark",
            completada:  "bg-success",
        }[status] || "bg-light text-dark";
    }

    /* ---------- Almacenamiento (localStorage como "base de datos") ----- */
    const KEYS = {
        users:   "taskflow_users",
        tasks:   "taskflow_tasks",
        session: "taskflow_session",
        seeded:  "taskflow_seeded",
    };

    const store = {
        get(key, fallback) {
            try { return JSON.parse(localStorage.getItem(key)) ?? fallback; }
            catch (_) { return fallback; }
        },
        set(key, value) { localStorage.setItem(key, JSON.stringify(value)); },
    };

    const getUsers   = () => store.get(KEYS.users, []);
    const setUsers   = (u) => store.set(KEYS.users, u);
    const getTasks   = () => store.get(KEYS.tasks, []);
    const setTasks   = (t) => store.set(KEYS.tasks, t);
    const getSession = () => store.get(KEYS.session, null);
    const setSession = (s) => store.set(KEYS.session, s);

    function nextId(list) {
        return list.reduce((m, x) => Math.max(m, x.id), 0) + 1;
    }

    /* ---------- Datos de muestra (equivalente a setup_sqlite.php) ------ */
    function seedIfNeeded() {
        if (store.get(KEYS.seeded, false)) return;

        const users = [
            { id: 1, name: "Ana Torres",  email: "ana@taskflow.test",    password: "password123" },
            { id: 2, name: "Carlos Ruiz", email: "carlos@taskflow.test", password: "password123" },
        ];

        const today = new Date();
        const iso = (d) => {
            const x = new Date(today);
            x.setDate(x.getDate() + d);
            return x.toISOString().slice(0, 10);
        };
        const now = new Date().toISOString().slice(0, 19).replace("T", " ");

        let id = 0;
        const t = (user_id, title, description, due_date, status) => ({
            id: ++id, user_id, title, description, due_date, status,
            created_at: now, updated_at: now,
        });

        const tasks = [
            t(1, "Preparar informe mensual de ventas", "Consolidar cifras y generar el PDF para dirección.", iso(3), "en_progreso"),
            t(1, "Enviar facturas pendientes a clientes", "Revisar las facturas vencidas antes de enviarlas.", iso(-2), "pendiente"),
            t(1, "Reunión con el equipo de diseño", "Definir la nueva paleta de colores del producto.", iso(-5), "completada"),
            t(1, "Actualizar la documentación de la API", null, null, "pendiente"),
            t(1, "Revisar los pull requests del repositorio", "Quedan 4 PRs por revisar y aprobar.", iso(1), "en_progreso"),
            t(1, "Planificar el próximo sprint", "Preparar el backlog y estimar las historias de usuario.", iso(7), "pendiente"),
            t(1, "Hacer copia de seguridad de la base de datos", null, iso(-10), "completada"),
            t(2, "Configurar el servidor de pruebas", "Instalar PHP 8.2 y configurar el virtual host.", iso(-1), "completada"),
            t(2, "Migrar la base de datos a producción", "Coordinar ventana de mantenimiento con el equipo.", iso(2), "en_progreso"),
            t(2, "Escribir tests unitarios del módulo de tareas", null, iso(5), "pendiente"),
            t(2, "Optimizar las consultas del dashboard", "Añadir índices y revisar los planes de ejecución.", null, "pendiente"),
            t(2, "Desplegar la versión 1.2.0", "Incluye soporte para SQLite y mejoras de seguridad.", iso(4), "en_progreso"),
            t(2, "Documentar el proceso de despliegue", null, iso(-3), "completada"),
        ];

        setUsers(users);
        setTasks(tasks);
        store.set(KEYS.seeded, true);
    }

    /* ---------- Utilidades ---------------------------------------------- */
    function e(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function formatDate(ymd) {
        if (!ymd) return null;
        const [y, m, d] = ymd.split("-");
        return `${d}/${m}/${y}`;
    }

    let flashes = [];
    function flash(type, message) { flashes.push({ type, message }); }

    function renderFlashes() {
        const box = document.getElementById("flash");
        box.innerHTML = flashes.map((f) => `
            <div class="alert alert-${e(f.type)} alert-dismissible fade show" role="alert">
                ${e(f.message)}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>`).join("");
        flashes = [];
        document.querySelectorAll("#flash .alert-dismissible").forEach((al) => {
            setTimeout(() => {
                if (window.bootstrap && bootstrap.Alert) {
                    bootstrap.Alert.getOrCreateInstance(al).close();
                }
            }, 4000);
        });
    }

    function go(hash) { window.location.hash = hash; }

    /* ---------- Autenticación ------------------------------------------ */
    const isLoggedIn = () => !!getSession();
    const currentUserId = () => (getSession() || {}).user_id ?? null;
    const currentUserName = () => (getSession() || {}).user_name ?? "";

    /* ---------- Navbar -------------------------------------------------- */
    function renderNavbar() {
        const nav = document.getElementById("navbar");
        if (!isLoggedIn()) { nav.innerHTML = ""; return; }
        nav.innerHTML = `
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
            <div class="container">
                <a class="navbar-brand fw-bold" href="#/"><i class="bi bi-check2-square"></i> TaskFlow</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link" href="#/"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="#/tasks"><i class="bi bi-list-task"></i> Tareas</a></li>
                        <li class="nav-item"><a class="nav-link" href="#/tasks/new"><i class="bi bi-plus-circle"></i> Nueva tarea</a></li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> ${e(currentUserName())}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item text-danger" href="#/logout"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>`;
    }

    /* ---------- Vistas: Login / Registro -------------------------------- */
    function viewLogin() {
        return `
        <div class="row justify-content-center">
          <div class="col-12 col-sm-10 col-md-7 col-lg-5">
            <div class="text-center mt-4 mb-3">
              <h1 class="display-6 fw-bold text-primary"><i class="bi bi-check2-square"></i> TaskFlow</h1>
              <p class="text-muted">Sistema Web de Gestión de Tareas</p>
            </div>
            <div class="alert alert-info small">
              <i class="bi bi-info-circle"></i> <strong>Demo:</strong> los datos se guardan solo en tu navegador.
              Prueba con <strong>ana@taskflow.test</strong> / <strong>password123</strong>.
            </div>
            <div class="card shadow-sm border-0">
              <div class="card-body p-4">
                <h2 class="h5 mb-4 text-center">Iniciar sesión</h2>
                <div id="formErrors"></div>
                <form id="loginForm" novalidate>
                  <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" name="email" class="form-control" value="ana@taskflow.test" required autofocus>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" value="password123" required>
                  </div>
                  <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Entrar</button>
                </form>
                <p class="text-center mt-3 mb-0 small">¿No tienes cuenta? <a href="#/register">Regístrate</a></p>
              </div>
            </div>
          </div>
        </div>`;
    }

    function bindLogin() {
        document.getElementById("loginForm").addEventListener("submit", (ev) => {
            ev.preventDefault();
            const f = ev.target;
            const email = f.email.value.trim();
            const password = f.password.value;
            const errBox = document.getElementById("formErrors");

            if (!email || !password) {
                errBox.innerHTML = errorAlert(["Introduce tu correo y contraseña."]);
                return;
            }
            const user = getUsers().find((u) => u.email === email && u.password === password);
            if (!user) {
                errBox.innerHTML = errorAlert(["Credenciales incorrectas. Verifica tu correo y contraseña."]);
                return;
            }
            setSession({ user_id: user.id, user_name: user.name });
            go("#/");
        });
    }

    function viewRegister() {
        return `
        <div class="row justify-content-center">
          <div class="col-12 col-sm-10 col-md-7 col-lg-5">
            <div class="card shadow-sm border-0 mt-4">
              <div class="card-body p-4">
                <h1 class="h4 text-center mb-1"><i class="bi bi-person-plus text-primary"></i> Crear cuenta</h1>
                <p class="text-center text-muted small mb-4">Regístrate para empezar a gestionar tus tareas</p>
                <div id="formErrors"></div>
                <form id="registerForm" novalidate>
                  <div class="mb-3"><label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" required autofocus></div>
                  <div class="mb-3"><label class="form-label">Correo electrónico</label>
                    <input type="email" name="email" class="form-control" required></div>
                  <div class="mb-3"><label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" minlength="6" required></div>
                  <div class="mb-3"><label class="form-label">Repetir contraseña</label>
                    <input type="password" name="password_confirm" class="form-control" minlength="6" required></div>
                  <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check2"></i> Registrarme</button>
                </form>
                <p class="text-center mt-3 mb-0 small">¿Ya tienes cuenta? <a href="#/login">Inicia sesión</a></p>
              </div>
            </div>
          </div>
        </div>`;
    }

    function bindRegister() {
        document.getElementById("registerForm").addEventListener("submit", (ev) => {
            ev.preventDefault();
            const f = ev.target;
            const name = f.name.value.trim();
            const email = f.email.value.trim();
            const password = f.password.value;
            const confirm = f.password_confirm.value;
            const errors = [];

            if (!name) errors.push("El nombre es obligatorio.");
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push("Introduce un correo electrónico válido.");
            if (password.length < 6) errors.push("La contraseña debe tener al menos 6 caracteres.");
            if (password !== confirm) errors.push("Las contraseñas no coinciden.");
            if (!errors.length && getUsers().some((u) => u.email === email)) {
                errors.push("Ya existe una cuenta con ese correo electrónico.");
            }
            if (errors.length) {
                document.getElementById("formErrors").innerHTML = errorAlert(errors);
                return;
            }
            const users = getUsers();
            users.push({ id: nextId(users), name, email, password });
            setUsers(users);
            flash("success", "Cuenta creada con éxito. Ya puedes iniciar sesión.");
            go("#/login");
        });
    }

    function errorAlert(list) {
        return `<div class="alert alert-danger"><ul class="mb-0 ps-3">${
            list.map((x) => `<li>${e(x)}</li>`).join("")}</ul></div>`;
    }

    /* ---------- Vista: Dashboard ---------------------------------------- */
    function viewDashboard() {
        const uid = currentUserId();
        const mine = getTasks().filter((t) => t.user_id === uid);
        const counts = { pendiente: 0, en_progreso: 0, completada: 0 };
        mine.forEach((t) => { counts[t.status] = (counts[t.status] || 0) + 1; });
        const total = mine.length;
        const pct = total > 0 ? Math.round(counts.completada / total * 100) : 0;
        const recent = [...mine].sort((a, b) => b.id - a.id).slice(0, 5);

        const card = (cls, value, label, icon) => `
            <div class="col-6 col-lg-3">
              <div class="card ${cls} shadow-sm border-0 h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div><div class="display-5 fw-bold">${value}</div><div class="small">${label}</div></div>
                  <i class="bi ${icon} fs-1 opacity-50"></i>
                </div>
              </div></div>
            </div>`;

        return `
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1 class="h3 mb-0">Hola, ${e(currentUserName())} 👋</h1>
          <a href="#/tasks/new" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nueva tarea</a>
        </div>
        <div class="row g-3 mb-4">
          ${card("text-bg-primary", total, "Total de tareas", "bi-list-task")}
          ${card("text-bg-secondary", counts.pendiente, "Pendientes", "bi-hourglass-split")}
          ${card("text-bg-warning", counts.en_progreso, "En progreso", "bi-arrow-repeat")}
          ${card("text-bg-success", counts.completada, "Completadas", "bi-check2-circle")}
        </div>
        ${total > 0 ? `
        <div class="card shadow-sm border-0 mb-4"><div class="card-body">
          <div class="d-flex justify-content-between mb-1">
            <span class="fw-semibold">Progreso general</span>
            <span class="text-muted">${pct}% completado</span>
          </div>
          <div class="progress" style="height:12px;">
            <div class="progress-bar bg-success" style="width:${pct}%"></div>
          </div>
        </div></div>` : ""}
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-clock-history"></i> Tareas recientes</span>
            <a href="#/tasks" class="small">Ver todas</a>
          </div>
          <div class="card-body p-0">
            ${recent.length === 0 ? `
              <div class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Aún no tienes tareas. ¡Crea la primera!</div>
            ` : `<div class="list-group list-group-flush">${recent.map((t) => `
              <a href="#/tasks/edit/${t.id}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span class="text-truncate me-2">${e(t.title)}</span>
                <span class="badge ${badgeClass(t.status)}">${e(STATUSES[t.status])}</span>
              </a>`).join("")}</div>`}
          </div>
        </div>`;
    }

    /* ---------- Vista: Listado de tareas -------------------------------- */
    function viewTasks(params) {
        const uid = currentUserId();
        const search = (params.get("q") || "").trim();
        let status = params.get("status") || "";
        if (!STATUSES[status]) status = "";

        let list = getTasks().filter((t) => t.user_id === uid);
        if (search) list = list.filter((t) => t.title.toLowerCase().includes(search.toLowerCase()));
        if (status) list = list.filter((t) => t.status === status);
        list.sort((a, b) => {
            const so = STATUS_ORDER[a.status] - STATUS_ORDER[b.status];
            if (so !== 0) return so;
            const an = a.due_date ? 0 : 1, bn = b.due_date ? 0 : 1;
            if (an !== bn) return an - bn;
            if (a.due_date && b.due_date && a.due_date !== b.due_date) return a.due_date < b.due_date ? -1 : 1;
            return b.id - a.id;
        });

        const options = Object.entries(STATUSES).map(([k, v]) =>
            `<option value="${k}" ${status === k ? "selected" : ""}>${e(v)}</option>`).join("");

        const filterForm = `
        <form id="filterForm" class="card card-body shadow-sm border-0 mb-4">
          <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
              <label class="form-label small mb-1">Buscar por nombre</label>
              <div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control" placeholder="Escribe el título…" value="${e(search)}"></div>
            </div>
            <div class="col-8 col-md-4">
              <label class="form-label small mb-1">Estado</label>
              <select name="status" class="form-select"><option value="">Todos</option>${options}</select>
            </div>
            <div class="col-4 col-md-2 d-grid">
              <button type="submit" class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filtrar</button>
            </div>
          </div>
          ${(search || status) ? `<div class="mt-2"><a href="#/tasks" class="small text-decoration-none"><i class="bi bi-x-circle"></i> Limpiar filtros</a></div>` : ""}
        </form>`;

        const header = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h1 class="h3 mb-0"><i class="bi bi-list-task"></i> Mis tareas</h1>
          <a href="#/tasks/new" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nueva</a>
        </div>`;

        if (list.length === 0) {
            return header + filterForm + `
            <div class="card shadow-sm border-0"><div class="card-body text-center text-muted py-5">
              <i class="bi bi-inbox fs-1 d-block mb-2"></i>No se encontraron tareas con esos criterios.
            </div></div>`;
        }

        const rows = list.map((t) => `
          <tr>
            <td><div class="fw-semibold">${e(t.title)}</div>
              ${t.description ? `<div class="small text-muted text-truncate" style="max-width:360px;">${e(t.description)}</div>` : ""}</td>
            <td><span class="badge ${badgeClass(t.status)}">${e(STATUSES[t.status])}</span></td>
            <td>${t.due_date ? e(formatDate(t.due_date)) : '<span class="text-muted">—</span>'}</td>
            <td class="text-end text-nowrap">
              <a href="#/tasks/edit/${t.id}" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
              <button class="btn btn-sm btn-outline-danger" title="Eliminar" data-delete="${t.id}"><i class="bi bi-trash"></i></button>
            </td>
          </tr>`).join("");

        const cards = list.map((t) => `
          <div class="card shadow-sm border-0 mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <h2 class="h6 mb-1">${e(t.title)}</h2>
              <span class="badge ${badgeClass(t.status)} ms-2">${e(STATUSES[t.status])}</span>
            </div>
            ${t.description ? `<p class="small text-muted mb-2">${e(t.description)}</p>` : ""}
            <div class="small text-muted mb-2"><i class="bi bi-calendar-event"></i> ${t.due_date ? e(formatDate(t.due_date)) : "Sin fecha"}</div>
            <div class="d-flex gap-2">
              <a href="#/tasks/edit/${t.id}" class="btn btn-sm btn-outline-secondary flex-fill"><i class="bi bi-pencil"></i> Editar</a>
              <button class="btn btn-sm btn-outline-danger flex-fill" data-delete="${t.id}"><i class="bi bi-trash"></i> Eliminar</button>
            </div>
          </div></div>`).join("");

        return header + filterForm + `
          <div class="card shadow-sm border-0 d-none d-md-block"><div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light"><tr><th>Título</th><th>Estado</th><th>Fecha límite</th><th class="text-end">Acciones</th></tr></thead>
              <tbody>${rows}</tbody>
            </table>
          </div></div>
          <div class="d-md-none">${cards}</div>`;
    }

    function bindTasks() {
        const form = document.getElementById("filterForm");
        if (form) {
            form.addEventListener("submit", (ev) => {
                ev.preventDefault();
                const q = form.q.value.trim();
                const status = form.status.value;
                const qs = new URLSearchParams();
                if (q) qs.set("q", q);
                if (status) qs.set("status", status);
                go("#/tasks" + (qs.toString() ? "?" + qs.toString() : ""));
            });
        }
        document.querySelectorAll("[data-delete]").forEach((btn) => {
            btn.addEventListener("click", () => {
                if (!window.confirm("¿Eliminar esta tarea?")) return;
                const id = parseInt(btn.getAttribute("data-delete"), 10);
                const uid = currentUserId();
                const before = getTasks();
                const after = before.filter((t) => !(t.id === id && t.user_id === uid));
                setTasks(after);
                flash(after.length < before.length ? "success" : "warning",
                      after.length < before.length ? "Tarea eliminada correctamente." : "No se encontró la tarea a eliminar.");
                render();
            });
        });
    }

    /* ---------- Vista: Crear / Editar ----------------------------------- */
    function taskForm(task) {
        const isEdit = !!task;
        const v = task || { title: "", description: "", due_date: "", status: "pendiente" };
        const options = Object.entries(STATUSES).map(([k, lbl]) =>
            `<option value="${k}" ${v.status === k ? "selected" : ""}>${e(lbl)}</option>`).join("");
        return `
        <div class="row justify-content-center"><div class="col-12 col-lg-8">
          <h1 class="h3 mb-4"><i class="bi bi-${isEdit ? "pencil-square" : "plus-circle"}"></i> ${isEdit ? "Editar" : "Nueva"} tarea</h1>
          <div id="formErrors"></div>
          <div class="card shadow-sm border-0"><div class="card-body p-4">
            <form id="taskForm" novalidate>
              <input type="hidden" name="id" value="${isEdit ? task.id : ""}">
              <div class="mb-3"><label class="form-label">Título <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="${e(v.title)}" required autofocus></div>
              <div class="mb-3"><label class="form-label">Descripción</label>
                <textarea name="description" class="form-control" rows="4">${e(v.description || "")}</textarea></div>
              <div class="row">
                <div class="col-12 col-sm-6 mb-3"><label class="form-label">Fecha límite</label>
                  <input type="date" name="due_date" class="form-control" value="${e(v.due_date || "")}"></div>
                <div class="col-12 col-sm-6 mb-3"><label class="form-label">Estado</label>
                  <select name="status" class="form-select">${options}</select></div>
              </div>
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> ${isEdit ? "Guardar cambios" : "Guardar tarea"}</button>
                <a href="#/tasks" class="btn btn-outline-secondary">Cancelar</a>
              </div>
            </form>
          </div></div>
        </div></div>`;
    }

    function bindTaskForm(existing) {
        document.getElementById("taskForm").addEventListener("submit", (ev) => {
            ev.preventDefault();
            const f = ev.target;
            const title = f.title.value.trim();
            const description = f.description.value.trim();
            const due_date = f.due_date.value;
            const status = f.status.value;
            const errors = [];
            if (!title) errors.push("El título es obligatorio.");
            if (!STATUSES[status]) errors.push("El estado seleccionado no es válido.");
            if (errors.length) {
                document.getElementById("formErrors").innerHTML = errorAlert(errors);
                return;
            }
            const tasks = getTasks();
            const now = new Date().toISOString().slice(0, 19).replace("T", " ");
            if (existing) {
                const t = tasks.find((x) => x.id === existing.id && x.user_id === currentUserId());
                if (t) {
                    Object.assign(t, { title, description: description || null, due_date: due_date || null, status, updated_at: now });
                    setTasks(tasks);
                    flash("success", "Tarea actualizada correctamente.");
                }
            } else {
                tasks.push({
                    id: nextId(tasks), user_id: currentUserId(),
                    title, description: description || null, due_date: due_date || null,
                    status, created_at: now, updated_at: now,
                });
                setTasks(tasks);
                flash("success", "Tarea creada correctamente.");
            }
            go("#/tasks");
        });
    }

    /* ---------- Router -------------------------------------------------- */
    function parseHash() {
        const raw = window.location.hash.replace(/^#/, "") || "/";
        const [path, query] = raw.split("?");
        return { path, params: new URLSearchParams(query || "") };
    }

    function render() {
        seedIfNeeded();
        const { path, params } = parseHash();
        const app = document.getElementById("app");

        // Logout
        if (path === "/logout") {
            setSession(null);
            go("#/login");
            return;
        }

        const publicRoutes = ["/login", "/register"];

        // Guard: rutas privadas requieren sesión
        if (!isLoggedIn() && !publicRoutes.includes(path)) { go("#/login"); return; }
        // Si ya hay sesión, no mostrar login/registro
        if (isLoggedIn() && publicRoutes.includes(path)) { go("#/"); return; }

        renderNavbar();

        let after = null;
        if (path === "/login")        { app.innerHTML = viewLogin();    after = bindLogin; }
        else if (path === "/register"){ app.innerHTML = viewRegister(); after = bindRegister; }
        else if (path === "/" )       { app.innerHTML = viewDashboard(); }
        else if (path === "/tasks")   { app.innerHTML = viewTasks(params); after = bindTasks; }
        else if (path === "/tasks/new"){ app.innerHTML = taskForm(null); after = () => bindTaskForm(null); }
        else if (path.startsWith("/tasks/edit/")) {
            const id = parseInt(path.split("/").pop(), 10);
            const task = getTasks().find((t) => t.id === id && t.user_id === currentUserId());
            if (!task) { flash("danger", "La tarea no existe o no tienes permiso para verla."); go("#/tasks"); return; }
            app.innerHTML = taskForm(task); after = () => bindTaskForm(task);
        } else { app.innerHTML = viewDashboard(); }

        renderFlashes();
        if (after) after();
        window.scrollTo(0, 0);
    }

    /* ---------- Arranque ------------------------------------------------ */
    document.getElementById("year").textContent = new Date().getFullYear();
    window.addEventListener("hashchange", render);
    document.addEventListener("DOMContentLoaded", render);
    render();
})();
