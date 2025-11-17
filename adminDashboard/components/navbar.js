class CustomNavbar extends HTMLElement {
  connectedCallback() {
    this.attachShadow({ mode: "open" });
    this.shadowRoot.innerHTML = `
      <style>
        nav {
          background-color: white;
          color: #dc2626;
          padding: 0.4rem 1rem;
          display: flex;
          justify-content: space-between;
          align-items: center;
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          height: 50px;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
          z-index: 1000;
        }

        .logo {
          display: flex;
          align-items: center;
          font-weight: 600;
          font-size: 1.1rem;
          color: #dc2626;
        }

        .logo-icon {
          margin-right: 0.5rem;
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
          gap: 1rem;
          position: relative;
        }

        .user-role {
          font-size: 0.95rem;
          color: #dc2626;
          font-weight: 600;
        }

        .user-avatar {
          width: 36px;
          height: 36px;
          border-radius: 50%;
          background-color: #dc2626;
          color: white;
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: bold;
          cursor: pointer;
          transition: transform 0.2s ease;
        }

        .user-avatar:hover {
          transform: scale(1.05);
        }

        .notification-icon {
          position: relative;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          color: black; /* bell is black */
        }

        .notification-badge {
          position: absolute;
          top: -4px;
          right: -4px;
          width: 16px;
          height: 16px;
          background-color: #dc2626;
          color: white;
          border-radius: 50%;
          font-size: 10px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: bold;
        }

        .dropdown-menu {
          position: absolute;
          right: 0;
          top: 55px;
          background: white;
          border-radius: 8px;
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
          width: 180px;
          display: none;
          z-index: 2000;
          padding: 0.5rem 0;
        }

        .dropdown-menu.active {
          display: block;
        }

        .dropdown-item {
          padding: 0.5rem 1rem;
          color: #4b5563;
          text-decoration: none;
          display: block;
          transition: background-color 0.2s ease;
        }

        .dropdown-item:hover {
          background-color: #fee2e2;
          color: #dc2626;
        }

        .sidebar-toggle {
          display: none;
          cursor: pointer;
          color: #dc2626;
        }

        @media (max-width: 768px) {
          .sidebar-toggle {
            display: block;
          }

          .logo span {
            font-size: 0.95rem;
          }

          .user-role {
            display: none;
          }

          .user-avatar {
            width: 32px;
            height: 32px;
            font-size: 0.8rem;
          }
        }
      </style>

      <nav>
        <div class="logo">
          <img src="../img/bsu.png" alt="CICS Logo" class="logo-img" />
          <span>CICS Emergency & Important Alerts</span>
        </div>
        <div class="user-menu">
          <div class="sidebar-toggle" id="sidebarToggle">
            <i data-feather="menu"></i>
          </div>
          <span class="user-role">Admin</span>
          <div class="user-avatar" id="userMenu">AD</div>
          <div class="notification-icon">
            <i data-feather="bell"></i>
            <span class="notification-badge">3</span>
          </div>
          <div class="dropdown-menu" id="dropdownMenu">
            <a href="settings.html" class="dropdown-item">Profile</a>
            <a href="../loginSignup/logout/php" class="dropdown-item">Logout</a>
          </div>
        </div>
      </nav>
    `;

    // Event handling
    const userMenu = this.shadowRoot.getElementById("userMenu");
    const dropdownMenu = this.shadowRoot.getElementById("dropdownMenu");
    const sidebarToggle = this.shadowRoot.getElementById("sidebarToggle");
    const sidebar = document.querySelector("custom-sidebar")?.shadowRoot?.getElementById("sidebar");

    userMenu.addEventListener("click", () =>
      dropdownMenu.classList.toggle("active")
    );

    document.addEventListener("click", (e) => {
      if (!e.composedPath().includes(this.shadowRoot))
        dropdownMenu.classList.remove("active");
    });

    if (sidebarToggle && sidebar) {
      sidebarToggle.addEventListener("click", () =>
        sidebar.classList.toggle("active")
      );
    }

    // Feather icons
    if (window.feather) {
      const icons = this.shadowRoot.querySelectorAll("[data-feather]");
      icons.forEach((icon) => {
        const name = icon.getAttribute("data-feather");
        const color =
          name === "bell"
            ? "black" // Notification bell black
            : "#dc2626"; // Logo and others red
        const svg = window.feather.icons[name].toSvg({
          color,
          width: 20,
          height: 20,
        });
        icon.outerHTML = svg;
      });
    }
  }
}

customElements.define("custom-navbar", CustomNavbar);
