@extends('layouts.app')

@section('title', 'Регистрация #' . $group->number)
@section('page-title', 'Регистрация #' . $group->number)

@section('page-actions')
<div class="btn-group" role="group">
    <a href="{{ route('registrations.create') }}" class="btn btn-outline-primary">
        <i class="bi bi-plus-circle"></i> Новая регистрация
    </a>
    @if(auth()->user()->role === 'admin')
        <a href="{{ route('admin.registrations') }}" class="btn btn-outline-secondary">
            <i class="bi bi-list"></i> Все регистрации
        </a>
    @endif
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <!-- Основная информация -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Информация о коллективе</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Название коллектива:</strong><br>
                        {{ $group->collective_name }}
                    </div>
                    <div class="col-md-6">
                        <strong>Принадлежность:</strong><br>
                        {{ $group->accessory ?? 'Не указано' }}
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Руководители:</strong><br>
                        {{ $group->leaders }}
                    </div>
                    <div class="col-md-6">
                        <strong>Тренеры:</strong><br>
                        {{ $group->trainers ?? 'Не указано' }}
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Название танца:</strong><br>
                        {{ $group->dance_name }}
                    </div>
                    <div class="col-md-6">
                        <strong>Длительность:</strong><br>
                        {{ $group->duration }}
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Номинация и дисциплина -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Номинация и дисциплина</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Номинация:</strong><br>
                        {{ $group->nomination->name ?? 'Не указано' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Дисциплина:</strong><br>
                        {{ $group->discipline->name ?? 'Не указано' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Возрастная категория:</strong><br>
                        {{ $group->age->name ?? 'Не указано' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Категория:</strong><br>
                        {{ $group->category->name ?? 'Не указано' }}
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Участники -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Участники ({{ $group->participants_count }} чел.)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($group->participants as $participant)
                        <div class="col-md-4 mb-2">
                            <span class="badge bg-light text-dark">{{ $participant->name }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <!-- Дипломы и медали -->
        @if($group->diplomas->count() > 0 || $group->medals->count() > 0)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Дипломы и медали</h5>
            </div>
            <div class="card-body">
                @if($group->diplomas->count() > 0)
                    <div class="mb-3">
                        <h6>Дипломы ({{ $group->diplomas->sum('quantity') }} шт.)</h6>
                        @foreach($group->diplomas as $diploma)
                            <div class="alert alert-info">
                                <strong>Количество:</strong> {{ $diploma->quantity }} шт.<br>
                                <strong>Цена за штуку:</strong> {{ $diploma->price_per_diploma }} ₽<br>
                                <strong>Общая стоимость:</strong> {{ $diploma->total_amount }} ₽<br>
                                <strong>Статус:</strong> {{ $diploma->status_label }}
                                @if($diploma->participants_list)
                                    <br><strong>Участники:</strong><br>
                                    <small>{{ str_replace("\n", ", ", $diploma->participants_list) }}</small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
                
                @if($group->medals->count() > 0)
                    <div class="mb-3">
                        <h6>Медали ({{ $group->medals->sum('quantity') }} шт.)</h6>
                        @foreach($group->medals as $medal)
                            <div class="alert alert-warning">
                                <strong>Количество:</strong> {{ $medal->quantity }} шт.<br>
                                <strong>Цена за штуку:</strong> {{ $medal->price_per_medal }} ₽<br>
                                <strong>Общая стоимость:</strong> {{ $medal->total_amount }} ₽<br>
                                <strong>Статус:</strong> {{ $medal->status_label }}
                                @if($medal->participants_list)
                                    <br><strong>Участники:</strong><br>
                                    <small>{{ str_replace("\n", ", ", $medal->participants_list) }}</small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>
    
    <!-- Панель оплат -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Оплаты</h5>
            </div>
            <div class="card-body">
                @if($group->payments->count() > 0)
                    @foreach($group->payments as $payment)
                        <div class="mb-3 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ $payment->type_label }}</strong><br>
                                    <small class="text-muted">{{ $payment->quantity }} шт.</small>
                                </div>
                                <div class="text-end">
                                    <div class="h5 mb-0">{{ $payment->amount }} ₽</div>
                                    <span class="badge bg-{{ $payment->status === 'paid' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'danger') }}">
                                        {{ $payment->status_label }}
                                    </span>
                                </div>
                            </div>
                            @if($payment->paid_at)
                                <small class="text-muted">Оплачено: {{ $payment->paid_at->format('d.m.Y H:i') }}</small>
                            @endif
                            @if($payment->notes)
                                <br><small class="text-muted">{{ $payment->notes }}</small>
                            @endif
                        </div>
                    @endforeach
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <strong>Общая стоимость:</strong>
                        <strong class="text-primary">{{ $group->payments->sum('amount') }} ₽</strong>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <span>Оплачено:</span>
                        <span class="text-success">{{ $group->payments->where('status', 'paid')->sum('amount') }} ₽</span>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <span>Ожидает оплаты:</span>
                        <span class="text-warning">{{ $group->payments->where('status', 'pending')->sum('amount') }} ₽</span>
                    </div>
                @else
                    <p class="text-muted">Оплаты не найдены</p>
                @endif
            </div>
        </div>
        
        <!-- Статус регистрации -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Статус регистрации</h5>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <span class="badge bg-{{ $group->status === 0 ? 'success' : ($group->status === 1 ? 'warning' : 'danger') }} fs-6">
                        @if($group->status === 0)
                            Принята
                        @elseif($group->status === 1)
                            На рассмотрении
                        @else
                            Отклонена
                        @endif
                    </span>
                </div>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-6">
                        <strong>Номер заявки</strong><br>
                        <span class="h4">{{ $group->number }}</span>
                    </div>
                    <div class="col-6">
                        <strong>Дата регистрации</strong><br>
                        {{ $group->created_at->format('d.m.Y') }}
                    </div>
                </div>
                
                <hr>
                
                <div class="text-center">
                    <strong>Регистратор:</strong><br>
                    {{ $group->user->name ?? 'Не указано' }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
