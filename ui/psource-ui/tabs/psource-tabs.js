document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.psource-tabs').forEach(tabs => {
    const nav = tabs.querySelector('.psource-tabs-nav');
    if (!nav) return;
    const tabButtons = nav.querySelectorAll('.psource-tab');
    const panels = tabs.querySelectorAll('.psource-tab-panel');

    tabButtons.forEach(tab => {
      tab.addEventListener('click', function (e) {
        e.preventDefault();

        // Nur innerhalb dieser Tab-Gruppe arbeiten!
        tabButtons.forEach(t => t.classList.remove('active'));
        panels.forEach(p => p.classList.remove('active'));

        tab.classList.add('active');
        const panel = tabs.querySelector('.psource-tab-panel#' + tab.dataset.tab);
        if (panel) panel.classList.add('active');
      });
    });

    // Optional: Beim Laden den ersten Tab aktivieren, falls keiner aktiv ist
    if (!tabs.querySelector('.psource-tab.active')) {
      const firstTab = tabButtons[0];
      if (firstTab) firstTab.classList.add('active');
    }
    if (!tabs.querySelector('.psource-tab-panel.active')) {
      const firstPanel = panels[0];
      if (firstPanel) firstPanel.classList.add('active');
    }
  });
});