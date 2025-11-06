class CustomNavbar extends HTMLElement {
  connectedCallback() {
    this.attachShadow({ mode: "open" });
    this.shadowRoot.innerHTML = `
      <style>
        nav {
          background-color: white;
          padding: 0.75rem 1.5rem;
          display: flex;
          justify-content: space-between;
          align-items: center;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          z-index: 1000;
        }

        .logo {
          display: flex;
          align-items: center;
          font-weight: 600;
          font-size: 1.1rem;
          color: #dc2626;
        }

        .logo-img {
          width: 32px;
          height: 32px;
          border-radius: 50%;
          margin-right: 0.5rem;
        }

        .user-menu {
          display: flex;
          align-items: center;
          gap: 0.75rem;
          position: relative;
        }

        .user-avatar {
          width: 2.5rem;
          height: 2.5rem;
          border-radius: 9999px;
          background-color: #fee2e2;
          display: flex;
          align-items: center;
          justify-content: center;
          color: #e53e3e;
          font-weight: bold;
          font-size: 0.9rem;
          cursor: pointer;
          transition: background 0.2s;
        }

        .user-avatar:hover {
          background-color: #fecaca;
        }

        .notification-bell svg {
          width: 24px;
          height: 24px;
          color: #000;
          transition: transform 0.2s;
        }

        .notification-bell svg:hover {
          transform: scale(1.1);
        }

        .user-name {
          font-weight: bold;
          color: #e53e3e;
          font-size: 0.95rem;
        }

        .mobile-menu-button {
          display: none;
          background: none;
          border: none;
          cursor: pointer;
          padding: 0.25rem;
        }

        .mobile-menu-button svg {
          width: 28px;
          height: 28px;
          color: #e53e3e;
        }

        /* Dropdown */
        .dropdown-menu {
          position: absolute;
          top: 3.2rem;
          right: 0;
          background-color: white;
          border: 1px solid #e5e7eb;
          border-radius: 0.5rem;
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
          display: none;
          flex-direction: column;
          min-width: 160px;
          overflow: hidden;
          z-index: 1001;
        }

        .dropdown-item {
          padding: 0.75rem 1rem;
          text-decoration: none;
          color: #374151;
          font-size: 0.9rem;
          transition: background 0.2s, color 0.2s;
          display: block;
        }

        .dropdown-item:hover {
          background-color: #fee2e2;
          color: #dc2626;
        }

        .dropdown-menu.show {
          display: flex;
          animation: fadeIn 0.2s ease-in-out;
        }

        @keyframes fadeIn {
          from { opacity: 0; transform: translateY(-5px); }
          to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
          .user-name {
            display: none;
          }

          .user-avatar {
            width: 2rem;
            height: 2rem;
            font-size: 0.8rem;
          }

          .mobile-menu-button {
            display: block;
          }

          .logo span {
            display: none;
          }
        }
      </style>

      <nav>
        <div class="logo">
          <img src="../bsu.png" alt="CICS Logo" class="logo-img" />
          <span>CICS Emergency & Important Alerts</span>
        </div>

        <div class="user-menu">
          <span class="user-name">John Doe</span>

          <div class="user-avatar" id="avatarBtn">JD</div>

          <div class="notification-bell">
            <a href="alerts.html" aria-label="View alerts">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor"
                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   viewBox="0 0 24 24">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
              </svg>
            </a>
          </div>

          <button id="mobile-menu-button" class="mobile-menu-button" aria-label="Toggle menu">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <line x1="3" y1="12" x2="21" y2="12"></line>
              <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
          </button>

          <!-- Dropdown -->
          <div class="dropdown-menu" id="dropdownMenu">
            <a href="settings.html" class="dropdown-item">Profile</a>
            <a href="index.php" class="dropdown-item" id="logoutBtn">Logout</a>
          </div>
        </div>
      </nav>
    `;

    const shadow = this.shadowRoot;
    const avatarBtn = shadow.querySelector("#avatarBtn");
    const dropdownMenu = shadow.querySelector("#dropdownMenu");
    const mobileMenuButton = shadow.querySelector("#mobile-menu-button");

    // Toggle sidebar on mobile
    mobileMenuButton.addEventListener("click", () => {
      window.dispatchEvent(new CustomEvent("toggle-sidebar"));
    });

    // Toggle dropdown on avatar click
    avatarBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      dropdownMenu.classList.toggle("show");
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", (e) => {
      if (!shadow.contains(e.target)) {
        dropdownMenu.classList.remove("show");
      }
    });

    // Logout handler (optional demo)
    shadow.querySelector("#logoutBtn").addEventListener("click", () => {
      localStorage.removeItem("currentUser");
      alert("Logged out successfully!");
      window.location.href = "login.html";
    });
  }
}

customElements.define("custom-navbar", CustomNavbar);
