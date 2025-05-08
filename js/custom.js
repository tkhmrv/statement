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