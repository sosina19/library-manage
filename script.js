const messageBox = document.getElementById("message");
const authSection = document.getElementById("auth-section");
const appSection = document.getElementById("app-section");
const currentUserText = document.getElementById("current-user");
const loginPanel = document.getElementById("login-panel");
const signupPanel = document.getElementById("signup-panel");
const loginTab = document.getElementById("show-login");
const signupTab = document.getElementById("show-signup");

const booksBody = document.getElementById("books-body");
const recordsBody = document.getElementById("records-body");
const usersBody = document.getElementById("users-body");

const studentActions = document.getElementById("student-actions");
const librarianActions = document.getElementById("librarian-actions");
const adminActions = document.getElementById("admin-actions");
const recordsSection = document.getElementById("records-section");
const usersSection = document.getElementById("users-section");

let currentUser = null;
let currentSearch = "";
let activeAuthView = "login";

function setAuthView(view) {
    activeAuthView = view;
    const isLogin = view === "login";
    loginPanel.classList.toggle("hidden", !isLogin);
    signupPanel.classList.toggle("hidden", isLogin);
    loginTab.classList.toggle("active", isLogin);
    signupTab.classList.toggle("active", !isLogin);
    loginTab.setAttribute("aria-selected", isLogin ? "true" : "false");
    signupTab.setAttribute("aria-selected", isLogin ? "false" : "true");
}

function showMessage(text, isError = false) {
    messageBox.textContent = text;
    messageBox.className = isError ? "error" : "success";
}

async function apiRequest(action, payload = {}) {
    const method = action === "list_books" || action === "me" || action === "list_records" || action === "list_users" ? "GET" : "POST";
    const options = { method };
    if (method === "POST") {
        options.headers = { "Content-Type": "application/json" };
        options.body = JSON.stringify(payload);
    }
    const response = await fetch(`api.php?action=${encodeURIComponent(action)}&q=${encodeURIComponent(payload.q || "")}`, options);
    const data = await response.json();
    if (!data.success) {
        throw new Error(data.message || "Request failed.");
    }
    return data;
}

async function loadBooks() {
    const data = await apiRequest("list_books", { q: currentSearch });

    booksBody.innerHTML = "";
    data.books.forEach((book) => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${book.id}</td>
            <td>${book.title}</td>
            <td>${book.author}</td>
            <td>${book.isbn}</td>
            <td>${book.total_quantity}</td>
            <td>${book.available_quantity}</td>
        `;
        booksBody.appendChild(row);
    });
}

async function loadRecords() {
    if (!(currentUser && (currentUser.role === "librarian" || currentUser.role === "admin"))) {
        return;
    }
    const data = await apiRequest("list_records");
    recordsBody.innerHTML = "";
    data.records.forEach((record) => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${record.id}</td>
            <td>${record.book_title}</td>
            <td>${record.username}</td>
            <td>${record.borrowed_at}</td>
            <td>${record.returned_at || "-"}</td>
            <td>${record.returned_at ? "Returned" : "Borrowed"}</td>
        `;
        recordsBody.appendChild(row);
    });
}

async function loadUsers() {
    if (!(currentUser && currentUser.role === "admin")) {
        return;
    }
    const data = await apiRequest("list_users");
    usersBody.innerHTML = "";
    data.users.forEach((user) => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${user.id}</td>
            <td>${user.name}</td>
            <td>${user.username}</td>
            <td>${user.role}</td>
            <td>${user.created_at}</td>
        `;
        usersBody.appendChild(row);
    });
}

function applyRoleUI() {
    studentActions.classList.add("hidden");
    librarianActions.classList.add("hidden");
    adminActions.classList.add("hidden");
    recordsSection.classList.add("hidden");
    usersSection.classList.add("hidden");

    if (!currentUser) {
        authSection.classList.remove("hidden");
        appSection.classList.add("hidden");
        return;
    }

    authSection.classList.add("hidden");
    appSection.classList.remove("hidden");
    currentUserText.textContent = `${currentUser.name} (${currentUser.role})`;

    if (currentUser.role === "student") {
        studentActions.classList.remove("hidden");
    }
    if (currentUser.role === "librarian") {
        librarianActions.classList.remove("hidden");
        recordsSection.classList.remove("hidden");
    }
    if (currentUser.role === "admin") {
        adminActions.classList.remove("hidden");
        recordsSection.classList.remove("hidden");
        usersSection.classList.remove("hidden");
    }
}

async function refreshAll() {
    applyRoleUI();
    if (!currentUser) {
        return;
    }
    await loadBooks();
    await loadRecords();
    await loadUsers();
}

document.getElementById("signup-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    try {
        await apiRequest("signup", {
            name: document.getElementById("signup-name").value.trim(),
            username: document.getElementById("signup-username").value.trim(),
            password: document.getElementById("signup-password").value
        });
        showMessage("Signup successful. Please login.");
        event.target.reset();
        setAuthView("login");
    } catch (error) {
        showMessage(error.message, true);
    }
});

document.getElementById("login-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    try {
        const data = await apiRequest("login", {
            username: document.getElementById("login-username").value.trim(),
            password: document.getElementById("login-password").value
        });
        currentUser = data.user;
        showMessage("Login successful.");
        await refreshAll();
        event.target.reset();
    } catch (error) {
        showMessage(error.message, true);
    }
});

document.getElementById("logout-btn").addEventListener("click", async () => {
    try {
        await apiRequest("logout");
        currentUser = null;
        showMessage("Logged out.");
        applyRoleUI();
    } catch (error) {
        showMessage(error.message, true);
    }
});

document.getElementById("search-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    currentSearch = document.getElementById("search-input").value.trim();
    try {
        await loadBooks();
    } catch (error) {
        showMessage(error.message, true);
    }
});

document.getElementById("clear-search").addEventListener("click", async () => {
    currentSearch = "";
    document.getElementById("search-input").value = "";
    await loadBooks();
});

document.getElementById("borrow-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    try {
        await apiRequest("borrow", { book_id: Number(document.getElementById("borrow-book-id").value) });
        showMessage("Book borrowed.");
        event.target.reset();
        await refreshAll();
    } catch (error) {
        showMessage(error.message, true);
    }
});

document.getElementById("return-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    try {
        await apiRequest("return", { book_id: Number(document.getElementById("return-book-id").value) });
        showMessage("Book returned.");
        event.target.reset();
        await refreshAll();
    } catch (error) {
        showMessage(error.message, true);
    }
});

document.getElementById("add-book-btn").addEventListener("click", async () => {
    try {
        await apiRequest("add_book", {
            title: document.getElementById("title").value.trim(),
            author: document.getElementById("author").value.trim(),
            isbn: document.getElementById("isbn").value.trim(),
            quantity: Number(document.getElementById("quantity").value)
        });
        showMessage("Book added.");
        await refreshAll();
    } catch (error) {
        showMessage(error.message, true);
    }
});

document.getElementById("update-book-btn").addEventListener("click", async () => {
    try {
        await apiRequest("update_book", {
            id: Number(document.getElementById("book-id").value),
            title: document.getElementById("title").value.trim(),
            author: document.getElementById("author").value.trim(),
            isbn: document.getElementById("isbn").value.trim(),
            quantity: Number(document.getElementById("quantity").value)
        });
        showMessage("Book updated.");
        await refreshAll();
    } catch (error) {
        showMessage(error.message, true);
    }
});

document.getElementById("delete-book-btn").addEventListener("click", async () => {
    try {
        await apiRequest("delete_book", { id: Number(document.getElementById("book-id").value) });
        showMessage("Book deleted.");
        await refreshAll();
    } catch (error) {
        showMessage(error.message, true);
    }
});

document.getElementById("create-user-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    try {
        await apiRequest("create_user", {
            name: document.getElementById("new-user-name").value.trim(),
            username: document.getElementById("new-user-username").value.trim(),
            password: document.getElementById("new-user-password").value,
            role: document.getElementById("new-user-role").value
        });
        showMessage("User created.");
        event.target.reset();
        await refreshAll();
    } catch (error) {
        showMessage(error.message, true);
    }
});

document.getElementById("remove-user-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    try {
        await apiRequest("delete_user", { id: Number(document.getElementById("remove-user-id").value) });
        showMessage("User removed.");
        event.target.reset();
        await refreshAll();
    } catch (error) {
        showMessage(error.message, true);
    }
});

loginTab.addEventListener("click", () => {
    setAuthView("login");
});

signupTab.addEventListener("click", () => {
    setAuthView("signup");
});

async function bootstrap() {
    try {
        const data = await apiRequest("me");
        currentUser = data.user;
    } catch (error) {
        currentUser = null;
    }
    setAuthView(activeAuthView);
    applyRoleUI();
    if (currentUser) {
        await refreshAll();
    }
}

bootstrap();
