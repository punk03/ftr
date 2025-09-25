<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'ФТР Регистрация')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .main-content {
            min-height: 100vh;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .table th {
            border-top: none;
            font-weight: 600;
        }
        .badge {
            font-size: 0.75em;
        }
        .autocomplete-suggestions {
            position: absolute;
            z-index: 1000;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
        }
        .autocomplete-suggestion {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .autocomplete-suggestion:hover {
            background-color: #f8f9fa;
        }
        .autocomplete-suggestion:last-child {
            border-bottom: none;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h5 class="text-primary">ФТР Регистрация</h5>
                        <small class="text-muted">{{ auth()->user()->name }}</small>
                        <br>
                        <span class="badge bg-secondary">{{ ucfirst(auth()->user()->role) }}</span>
                    </div>
                    
                    <ul class="nav flex-column">
                        @if(auth()->user()->role === 'admin')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                    <i class="bi bi-speedometer2"></i> Дашборд
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.events.*') ? 'active' : '' }}" href="{{ route('admin.events') }}">
                                    <i class="bi bi-calendar-event"></i> Мероприятия
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                                    <i class="bi bi-people"></i> Пользователи
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.registrations') ? 'active' : '' }}" href="{{ route('admin.registrations') }}">
                                    <i class="bi bi-list-ul"></i> Регистрации
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.statistics') ? 'active' : '' }}" href="{{ route('admin.statistics') }}">
                                    <i class="bi bi-graph-up"></i> Статистика
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.backup-settings') ? 'active' : '' }}" href="{{ route('admin.backup-settings') }}">
                                    <i class="bi bi-shield-check"></i> Резервные копии
                                </a>
                            </li>
                        @endif
                        
                        @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('registrations.*') ? 'active' : '' }}" href="{{ route('registrations.create') }}">
                                    <i class="bi bi-plus-circle"></i> Новая регистрация
                                </a>
                            </li>
                        @endif
                        
                        @if(in_array(auth()->user()->role, ['admin', 'statistician']))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('statistics.*') ? 'active' : '' }}" href="{{ route('statistics.dashboard') }}">
                                    <i class="bi bi-bar-chart"></i> Отчеты
                                </a>
                            </li>
                        @endif
                    </ul>
                    
                    <hr>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="bi bi-box-arrow-right"></i> Выход
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">@yield('page-title', 'Панель управления')</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        @yield('page-actions')
                    </div>
                </div>
                
                <!-- Alerts -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                @yield('content')
            </main>
        </div>
    </div>
    
    <!-- Logout form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>
