class CustomSidebar extends HTMLElement {
  connectedCallback() {
    this.attachShadow({ mode: 'open' });
    this.shadowRoot.innerHTML = `
      <style>
        /* ===== Overlay (mobile background dim) ===== */
        .overlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: rgba(0, 0, 0, 0.4);
          opacity: 0;
          visibility: hidden;
          transition: all 0.3s ease;
          z-index: 900;
        }
        .overlay.show {
          opacity: 1;
          visibility: visible;
        }

        /* ===== Sidebar container ===== */
        aside {
          background-color: #B91C1C; /* Main red background */
          width: 240px;
          height: 100vh;
          position: fixed;
          top: 60px; /* aligns below navbar */
          left: 0;
          box-shadow: 2px 0 6px rgba(0, 0, 0, 0.2);
          color: #F9FAFB; /* White text */
          transform: translateX(0);
          transition: transform 0.3s ease-in-out;
          z-index: 950;
          display: flex;
          flex-direction: column;
          overflow-y: auto;
        }

        /* ===== Mobile behavior ===== */
        @media (max-width: 768px) {
          aside {
            transform: translateX(-100%);
          }
          aside.open {
            transform: translateX(0);
          }
        }

        /* ===== Sidebar menu styling ===== */
        .sidebar-menu {
          display: flex;
          flex-direction: column;
          padding: 1rem 0;
          flex: 1;
        }

        .menu-item {
          display: flex;
          align-items: center;
          gap: 0.75rem;
          padding: 0.75rem 1.5rem;
          color: #F9FAFB; /* White text */
          text-decoration: none;
          font-weight: 500;
          transition: all 0.2s ease;
        }

        .menu-item:hover {
          background-color: #DC2626; /* Slightly lighter red */
          color: #FFFFFF;
        }

        .menu-item.active {
          background-color: #7F1D1D; /* Darker red for active state */
          color: #FFFFFF;
          border-left: 4px solid #FEE2E2; /* Soft pink highlight */
          font-weight: 600;
        }

        /* Hover only for non-active items */
        .menu-item.active:hover {
          background-color: #7F1D1D;
        }

        .menu-divider {
          height: 1px;
          background-color: #991B1B;
          margin: 0.75rem 1.5rem;
        }

        /* Logout at bottom */
        .menu-item.logout {
          margin-top: 10rem;
        }

        /* ===== Feather icons ===== */
        i {
          stroke: currentColor;
          flex-shrink: 0;
        }

        /* ===== Scrollbar ===== */
        aside::-webkit-scrollbar {
          width: 6px;
        }
        aside::-webkit-scrollbar-thumb {
          background-color: #FCA5A5;
          border-radius: 10px;
        }
        aside::-webkit-scrollbar-track {
          background: transparent;
        }
      </style>

      <div class="overlay"></div>

      <aside id="sidebar">
        <div class="sidebar-menu">
          <a href="index.php" class="menu-item active"><i data-feather="home"></i><span>Dashboard</span></a>
          <a href="sendAlert.html" class="menu-item"><i data-feather="send"></i><span>Send Alert</span></a>
          <a href="trackingAcknowledgement.html" class="menu-item"><i data-feather="check-square"></i><span>Acknowledgment</span></a>
          <a href="sosMonitoring.html" class="menu-item"><i data-feather="alert-circle"></i><span>SOS Monitoring</span></a>
          <a href="incidentReport.php" class="menu-item"><i data-feather="file-text"></i><span>Incident Report</span></a>
          <a href="analytics.html" class="menu-item"><i data-feather="bar-chart-2"></i></i><span>Analytics</span></a>
          <a href="activityLogs.html" class="menu-item"><i data-feather="layers"></i><span>Activity Logs</span></a>
          <a href="admin_hotlines.html" class="menu-item"><i data-feather="phone-call"></i><span>Emergency Hotlines</span></a>
          <a href="settings.html" class="menu-item"><i data-feather="settings"></i><span>Settings</span></a>

          <div class="menu-divider"></div>

          <a href="#" class="menu-item logout"><i data-feather="log-out"></i><span>Logout</span></a>
        </div>
      </aside>
    `;

    // Elements
    const overlay = this.shadowRoot.querySelector('.overlay');
    const aside = this.shadowRoot.querySelector('aside');

    // Load Feather icons in white
    this.shadowRoot.querySelectorAll('i').forEach(icon => {
      const name = icon.getAttribute('data-feather');
      icon.outerHTML = feather.icons[name].toSvg({ width: 18, height: 18, color: "#F9FAFB" });
    });

    // Toggle sidebar visibility (from navbar toggle)
    window.addEventListener('toggle-sidebar', () => {
      aside.classList.toggle('open');
      overlay.classList.toggle('show');
    });

    // Hide sidebar when overlay is clicked
    overlay.addEventListener('click', () => {
      aside.classList.remove('open');
      overlay.classList.remove('show');
    });
  }
}

customElements.define('custom-sidebar', CustomSidebar);
