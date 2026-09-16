document.addEventListener('DOMContentLoaded', function () {
  var body = document.body;
  var saved = localStorage.getItem('bpoTheme');
  if (saved === 'dark') body.classList.add('dark-mode');
  var t = document.getElementById('themeToggle');
  if (t) t.addEventListener('click', function () {
    body.classList.toggle('dark-mode');
    localStorage.setItem('bpoTheme', body.classList.contains('dark-mode') ? 'dark' : 'light');
  });
});
function openBpoModal(id){var el=document.getElementById(id);if(!el)return;el.classList.add('show');el.setAttribute('aria-hidden','false');}
function closeBpoModal(id){var el=document.getElementById(id);if(!el)return;el.classList.remove('show');el.setAttribute('aria-hidden','true');}
document.addEventListener('click',function(e){if(e.target&&e.target.classList&&e.target.classList.contains('modal-overlay')){e.target.classList.remove('show');}});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){document.querySelectorAll('.modal-overlay.show').forEach(function(el){el.classList.remove('show');});}});
