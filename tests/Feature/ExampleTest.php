<?php

it('alamat utama mengarahkan tamu ke halaman masuk', function () {
    $this->get('/')->assertRedirect('/masuk');
});
