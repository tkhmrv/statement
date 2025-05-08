



<form action="service/send_feedback_form.php" method="post" class="row">

    <div class="col-md-6 col-lg-6">
        <div class="mb-3">
            <label for="name" class="ps-3 mb-2">Имя *</label>
            <input class="form-control" type="text" autocomplete="on" name="name" minlength="3" maxlength="100" required
                pattern="^[A-Za-zА-Яа-яЁё\s]*$" id="name" value="<?php if (isset($user['name']))
                    echo htmlspecialchars($user['name']); ?>">
        </div>
    </div>

    <div class="col-md-6 col-lg-6">
        <div class="mb-3">
            <label for="email" class="ps-3 mb-2">Электронная почта *</label>
            <input class="form-control" type="email" autocomplete="on" name="email" id="email" required value="<?php if (isset($user['email']))
                echo htmlspecialchars($user['email']); ?>">
        </div>
    </div>

    <div class="col-md-6 col-lg-12">
        <div class="mb-3">
            <label for="subject" class="ps-3 mb-2">Тема *</label>
            <input type="text" class="form-control" id="subject" name="subject" minlength="3" maxlength="100" required
                pattern="^[A-Za-zА-Яа-яЁё\s]*$">
        </div>
    </div>

    <div class="col-md-12 col-lg-12">
        <div class="mb-3">
            <label for="message" class="ps-3 mb-2">Сообщение *</label>
            <textarea class="form-control" name="message" id="message" cols="30" rows="7" required maxlength="200"
                placeholder="Не более 200 символов"></textarea>
        </div>
    </div>

    <div class="col-md-12">
        <input type="submit" value="Отправить сообщение" class="btn btn-primary">
    </div>

</form>