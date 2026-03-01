// Compatibility helpers for legacy AdminLTE-era markup.
(function () {
  'use strict';

  var LAYOUT_COLLAPSED_KEY = 'ssas-layout-menu-collapsed';
  var NAVBAR_TOGGLE_SELECTOR = '[data-sidebar-toggle="true"]';

  function isSmallScreen() {
    if (window.Helpers && typeof window.Helpers.isSmallScreen === 'function') {
      return window.Helpers.isSmallScreen();
    }

    return window.innerWidth < 1200;
  }

  function setDesktopCollapsed(collapsed) {
    var root = document.documentElement;
    root.classList.toggle('layout-menu-collapsed', collapsed);
    root.classList.remove('layout-menu-hover');

    if (window.Helpers && typeof window.Helpers._triggerWindowEvent === 'function') {
      window.Helpers._triggerWindowEvent('resize');
    }

    try {
      window.localStorage.setItem(LAYOUT_COLLAPSED_KEY, collapsed ? '1' : '0');
    } catch (error) {
      // Ignore localStorage failures (private mode / restricted environments).
    }
  }

  function applyStoredDesktopState() {
    if (isSmallScreen()) {
      return;
    }

    try {
      var value = window.localStorage.getItem(LAYOUT_COLLAPSED_KEY);
      if (value === '1') {
        setDesktopCollapsed(true);
      } else if (value === '0') {
        setDesktopCollapsed(false);
      }
    } catch (error) {
      // Ignore localStorage failures (private mode / restricted environments).
    }
  }

  function patchSidebarToggle() {
    if (!window.Helpers || typeof window.Helpers.toggleCollapsed !== 'function') {
      return;
    }

    var originalSetCollapsed =
      typeof window.Helpers.setCollapsed === 'function'
        ? window.Helpers.setCollapsed.bind(window.Helpers)
        : null;
    var originalToggleCollapsed = window.Helpers.toggleCollapsed.bind(window.Helpers);

    window.Helpers.setCollapsed = function (collapsed, animate) {
      if (!isSmallScreen()) {
        setDesktopCollapsed(Boolean(collapsed));
        return;
      }

      if (originalSetCollapsed) {
        return originalSetCollapsed(collapsed, animate);
      }
    };

    window.Helpers.toggleCollapsed = function (animate) {
      if (!isSmallScreen()) {
        setDesktopCollapsed(!document.documentElement.classList.contains('layout-menu-collapsed'));
        return;
      }

      return originalToggleCollapsed(animate);
    };
  }

  function bindSidebarToggleFallback() {
    document.addEventListener(
      'click',
      function (event) {
        var toggle = event.target.closest(NAVBAR_TOGGLE_SELECTOR);
        if (!toggle) {
          return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (isSmallScreen()) {
          if (window.Helpers && typeof window.Helpers.toggleCollapsed === 'function') {
            window.Helpers.toggleCollapsed();
          }
          return;
        }

        setDesktopCollapsed(!document.documentElement.classList.contains('layout-menu-collapsed'));
      },
      true
    );
  }

  patchSidebarToggle();
  applyStoredDesktopState();
  bindSidebarToggleFallback();

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-dismiss="alert"], .alert .close');
    if (!trigger) {
      return;
    }

    var alertEl = trigger.closest('.alert');
    if (!alertEl) {
      return;
    }

    event.preventDefault();
    alertEl.classList.remove('show');
    setTimeout(function () {
      alertEl.remove();
    }, 120);
  });
})();
