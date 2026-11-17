<?php
if (!defined('PAGE_TITLE')) {
    $pageTitle = $pageTitle ?? 'Django Girls Sierra Leone';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> &bull; Django Girls Sierra Leone</title>
  <link rel="stylesheet" href="/assets/style.css">
  <style>
    :root {
      --green: #0c4b33;
      --green-dark: #073322;
      --green-soft: #e6f3ec;
      --pink: #e8457a;
      --pink-soft: #fde8ef;
      --paper: #f6f5f0;
      --ink: #182620;
      --muted: #55635c;
      --line: #d6dcd8;
    }
    body { font-family: Tahoma, Verdana, sans-serif; margin: 0; background: #faf9f6; color: var(--ink); line-height: 1.6; }
    .site-nav { background: var(--green); color: #fff; padding: 14px 28px; display: flex; justify-content: space-between; align-items: center; }
    .site-nav a { color: #fff; text-decoration: none; margin-left: 18px; font-weight: 500; }
    .site-nav a:hover { color: var(--pink-soft); }
    .container { max-width: 1200px; margin: 32px auto; padding: 0 20px; }
    .card { background: #fff; border-radius: 8px; border: 1px solid var(--line); padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .btn { display: inline-block; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; background: var(--pink); color: #fff; }
    .btn-ghost { background: transparent; border: 2px solid var(--pink); color: var(--pink); }
    .eyebrow { color: var(--pink); font-size: 0.85rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; }
  </style>
</head>
<body>
<nav class="site-nav">
  <div><strong>&#10024; Django Girls Sierra Leone</strong></div>
  <div>
    <a href="/public-website/home/index.php">Home</a>
    <a href="/public-website/about/portfolio.html">About</a>
    <a href="/public-website/events/events.php">Events</a>
    <a href="/public-website/resources/resources.php">Resources</a>
    <a href="/public-website/contact/contact.php">Contact</a>
    <a href="/member-dashboard/dashboard/dashboard.php">Dashboard</a>
    <a href="/public-website/login/login.php">Login</a>
  </div>
</nav>
<main class="container">
