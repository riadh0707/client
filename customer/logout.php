<?php
require_once __DIR__ . '/../includes/bootstrap.php';
logout_user();
flash('Vous êtes déconnectée. À bientôt !', 'info');
redirect('index.php');
