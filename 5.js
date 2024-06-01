
function old() {
    var responseJSON = JSON.parse(responseString);
    responseJSON.forEach(function(item, index){
        if (item.price = undefined) {
            item.price = 0;
        }
        orderSubtotal += item.price;
    });
    console.log( 'Стоимостьзаказа: ' + total> 0? 'Бесплатно': total + ' руб.');
}


function refactored(responseString) {

    // Добавляем предусловие.
    // Проверяем передается ли нужный нам параметр
    // JS довольно интересный язык, в котором не выдается ошибка даже когда параметр обязателен
    if (responseString === undefined) {
        throw Error('Parameter has to be passed');
    }

    const responseJSON = JSON.parse(responseString);

    if (responseJSON === null) {
        throw Error('...');
    }

    // Итог заказа
    let orderSubtotal = 0;

    responseJSON.forEach((item) => {
        // price не может быть undefined, обычно с бэка приходит null, если значение не присвоено
        if (item.price === null) {
            item.price = 0;
        }

        orderSubtotal += item.price;
    });

    console.log('Стоимость заказа: ' + orderSubtotal > 0 ? 'Бесплатно' : orderSubtotal + ' руб.');
}
