document.addEventListener('DOMContentLoaded', function() {
  var toggle = document.getElementById('navToggle');
  var collapse = document.getElementById('navCollapse');
  if (toggle && collapse) {
    toggle.addEventListener('click', function() {
      collapse.classList.toggle('open');
    });
  }
});
