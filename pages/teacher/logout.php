<?php
/**
 * 教师登出脚本（占位 - Phase 2 实现）
 */
require_once __DIR__ . '/../../includes/config.php';
session_destroy();
redirect('../../index.php');
