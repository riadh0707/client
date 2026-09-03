<?php
require_once __DIR__ . '/../includes/bootstrap.php';
logout_user();
flash('Déconnexion réussie. À bientôt !', 'info');
redirect('index.php');
