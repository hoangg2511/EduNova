@extends('layouts.app')

@section('title', 'Dashboard - EduNova')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Chào mừng bạn quay trở lại, {{ auth()->user()->name }}!</p>
    </div>

    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
            transition: all .3s;
        }

        .stat-card:hover {
            border-color: #4f46e5;
            box-shadow: 0 4px 12px rgba(79, 70, 229, .1);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 12px;
        }

        .stat-icon.blue { background-color: #eff6ff; color: #0c2d6b; }
        .stat-icon.green { background-color: #f0fdf4; color: #166534; }
        .stat-icon.purple { background-color: #faf5ff; color: #6b21a8; }
        .stat-icon.orange { background-color: #fff7ed; color: #7c2d12; }

        .stat-label {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
        }

        .content-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }
    </style>

    {{-- Stats Grid --}}
    <div class="stats-grid">
        {{-- Stat Card 1 --}}
        <div class="stat-card">
            <div class="stat-icon blue">👥</div>
            <p class="stat-label">Tổng Học Sinh</p>
            <p class="stat-value">0</p>
        </div>

        {{-- Stat Card 2 --}}
        <div class="stat-card">
            <div class="stat-icon green">📚</div>
            <p class="stat-label">Khoá Học</p>
            <p class="stat-value">0</p>
        </div>

        {{-- Stat Card 3 --}}
        <div class="stat-card">
            <div class="stat-icon purple">👨‍🏫</div>
            <p class="stat-label">Giáo Viên</p>
            <p class="stat-value">0</p>
        </div>

        {{-- Stat Card 4 --}}
        <div class="stat-card">
            <div class="stat-icon orange">✓</div>
            <p class="stat-label">Hoàn Thành</p>
            <p class="stat-value">0%</p>
        </div>
    </div>

    {{-- Welcome Section --}}
    <div class="content-section">
        <h2 class="section-title">Chào mừng đến EduNova</h2>
        <p style="color: #64748b; line-height: 1.8; margin-bottom: 16px;">
            EduNova là hệ thống quản lý học tập toàn diện, giúp bạn:
        </p>
        <ul style="color: #64748b; margin-left: 24px; line-height: 1.8;">
            <li>🎓 Quản lý học sinh và giáo viên</li>
            <li>📖 Tổ chức khoá học và bài học</li>
            <li>📊 Theo dõi tiến độ và điểm số</li>
            <li>💬 Giao tiếp hiệu quả với học viên</li>
        </ul>
        <p style="color: #64748b; margin-top: 16px;">
            Bắt đầu bằng cách chuyển đến các mục quản lý trong thanh điều hướng phía trên.
        </p>
    </div>
@endsection
