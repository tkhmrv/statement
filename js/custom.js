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
    document.querySelector('.toast-cookie-fixed').classList.add('hiding');
  }, 100);

  setTimeout(() => {
    document.querySelector('.toast-cookie-fixed').style.display = 'none';
  }, 1000);
});

// Проверка на наличие уведомления
if (document.cookie.indexOf('notificationShowed=true') === -1) {
  setTimeout(() => {
    document.querySelector('.notification-only').style.display = 'flex'; // показать
  }, 10000);

  setTimeout(() => {
    document.querySelector('.notification-only').classList.add('hiding');
  }, 18000);

  setTimeout(() => {
    document.querySelector('.notification-only').style.display = 'none'; // скрыть
    document.cookie = "notificationShowed=true; path=/; max-age=" + (60 * 60 * 24 * 7); // 1 неделя
  }, 20000);

} else {
  document.querySelector('.notification-only').style.display = 'none'; // скрыть
}