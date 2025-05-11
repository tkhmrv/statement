function updateDateTime() {
  const now = new Date();

  // Форматируем время
  const hours = now.getHours().toString().padStart(2, '0');
  const minutes = now.getMinutes().toString().padStart(2, '0');

  // Форматируем дату с русскими названиями месяцев
  const months = [
    'января', 'февраля', 'марта', 'апреля',
    'мая', 'июня', 'июля', 'августа',
    'сентября', 'октября', 'ноября', 'декабря'
  ];
  const day = now.getDate();
  const monthName = months[now.getMonth()];

  // Формируем строку
  const datetimeString = `${hours}:${minutes}, ${day} ${monthName}`;

  // Выводим на страницу
  document.getElementById('datetime').textContent = datetimeString;

  // Обновляем каждую минуту (60000 мс)
  setTimeout(updateDateTime, 10000);
}

// Первый запуск
updateDateTime();

// Проверка на наличие cookie
if (document.cookie.indexOf('privacyAccepted=true') === -1) {
  document.querySelector('.cookies-only').style.display = 'flex'; // показать
} else {
  document.querySelector('.cookies-only').style.display = 'none'; // скрыть
}

// Назначаем обработчик кнопке "Окей"
document.querySelector('.cookie-accept').addEventListener('click', function (e) {
  e.preventDefault(); // не переходим по ссылке
  document.cookie = "privacyAccepted=true; path=/; max-age=" + (60 * 60 * 24 * 365); // 1 год

  setTimeout(() => {
    document.querySelector('.cookies-only').classList.add('hiding');
  }, 100);

  setTimeout(() => {
    document.querySelector('.cookies-only').style.display = 'none';
  }, 1000);

  // Показываем уведомление через 10 секунд после скрытия плашки
  showNotificationWithDelay();
});

// --- НОВАЯ ЛОГИКА УВЕДОМЛЕНИЯ ---
function setNotificationShowedCookie() {
  document.cookie = "notificationShowed=true; path=/; max-age=" + (60 * 60 * 24 * 7); // 1 неделя
}

function showNotificationWithDelay() {
  // Проверяем, не показывали ли уведомление за последнюю неделю
  if (document.cookie.indexOf('notificationShowed=true') !== -1) return;
  setTimeout(() => {
    const notif = document.querySelector('.notification-only');
    notif.style.display = 'flex';
    setNotificationShowedCookie();
    // Скрываем уведомление через 10 секунд
    setTimeout(() => {
      notif.classList.add('hiding');
    }, 10000);
    // Полностью скрываем через 11 секунд
    setTimeout(() => {
      notif.style.display = 'none';
    }, 11000);
  }, 10000);
}

// Логика показа уведомления при загрузке страницы
if (document.cookie.indexOf('privacyAccepted=true') !== -1) {
  showNotificationWithDelay();
} else {
  document.querySelector('.notification-only').style.display = 'none'; // скрыть
}
