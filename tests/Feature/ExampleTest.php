<?php

it('sends visitors from the home page to the public notice board', function () {
    $this->get('/')->assertRedirect('/notices');
    $this->get('/notices')->assertOk();
});
