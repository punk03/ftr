@extends('layouts.app')

@section('title', 'Новая регистрация')
@section('page-title', 'Новая регистрация')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Регистрация коллектива</h5>
            </div>
            <div class="card-body">
                <form id="registration-form" method="POST" action="{{ route('registrations.store') }}">
                    @csrf
                    
                    <!-- Выбор мероприятия -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="event_id" class="form-label">Мероприятие <span class="text-danger">*</span></label>
                            <select class="form-select" id="event_id" name="event_id" required>
                                <option value="">Выберите мероприятие</option>
                                @foreach($events as $event)
                                    <option value="{{ $event->id }}">{{ $event->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <!-- Информация о коллективе -->
                    <h6 class="text-primary mb-3">Информация о коллективе</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="collective_name" class="form-label">Название коллектива <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="collective_name" name="collective_name" 
                                   value="{{ old('collective_name') }}" required autocomplete="off">
                            <div class="autocomplete-suggestions" id="collective-suggestions" style="display: none;"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="accessory" class="form-label">Принадлежность коллектива</label>
                            <input type="text" class="form-control" id="accessory" name="accessory" 
                                   value="{{ old('accessory') }}" autocomplete="off">
                            <div class="autocomplete-suggestions" id="accessory-suggestions" style="display: none;"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="leaders" class="form-label">Руководители <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="leaders" name="leaders" 
                                   value="{{ old('leaders') }}" required autocomplete="off">
                            <div class="autocomplete-suggestions" id="leaders-suggestions" style="display: none;"></div>
                            <small class="form-text text-muted">Несколько руководителей разделяйте запятыми</small>
                        </div>
                        <div class="col-md-6">
                            <label for="trainers" class="form-label">Тренеры</label>
                            <input type="text" class="form-control" id="trainers" name="trainers" 
                                   value="{{ old('trainers') }}" autocomplete="off">
                            <div class="autocomplete-suggestions" id="trainers-suggestions" style="display: none;"></div>
                            <small class="form-text text-muted">Несколько тренеров разделяйте запятыми</small>
                        </div>
                    </div>
                    
                    <!-- Участники -->
                    <div class="mb-3">
                        <label for="participants" class="form-label">Участники <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="participants" name="participants" rows="6" 
                                  placeholder="Введите имена участников, каждый на новой строке. Можно скопировать список из другого места - система автоматически очистит лишние символы и нумерацию." required>{{ old('participants') }}</textarea>
                        <small class="form-text text-muted">
                            Каждый участник на новой строке. Система автоматически очистит от лишних символов и нумерации.
                        </small>
                    </div>
                    
                    <!-- Номинация и дисциплина -->
                    <h6 class="text-primary mb-3">Номинация и дисциплина</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="nomination_id" class="form-label">Номинация <span class="text-danger">*</span></label>
                            <select class="form-select" id="nomination_id" name="nomination_id" required>
                                <option value="">Выберите номинацию</option>
                                <option value="1">Соло (1 чел)</option>
                                <option value="2">Дуэт/Пара (2 чел)</option>
                                <option value="3">Малая группа (от 3 до 7 чел)</option>
                                <option value="4">Формейшн (от 8 до 24 чел)</option>
                                <option value="5">Продакшн (от 25 и более чел)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="discipline_id" class="form-label">Дисциплина <span class="text-danger">*</span></label>
                            <select class="form-select" id="discipline_id" name="discipline_id" required>
                                <option value="">Выберите дисциплину</option>
                                <option value="1">Dance Show</option>
                                <option value="2">Народный танец</option>
                                <option value="3">Народный стилизованный танец</option>
                                <option value="4">Классический танец (балет и деми классика)</option>
                                <option value="5">Acro dance</option>
                                <option value="6">Modern</option>
                                <option value="7">Jazz</option>
                                <option value="8">Contemporary</option>
                                <option value="9">Песня и танец (song and dance)</option>
                                <option value="10">СЭТ (современный эстрадный танец)</option>
                                <option value="11">Экспериментальный танец</option>
                                <option value="12">СТК (свободная танцевальная категория)</option>
                                <option value="13">Street dance show</option>
                                <option value="14">Hip-Hop</option>
                                <option value="15">Break dance</option>
                                <option value="16">Оригинальный жанр</option>
                                <option value="17">Цирк</option>
                                <option value="18">Импровизация</option>
                                <option value="19">Belly dance</option>
                                <option value="20">Бальный танец</option>
                                <option value="21">Ирландские танцы</option>
                                <option value="22">Мюзикл</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="age_id" class="form-label">Возрастная категория <span class="text-danger">*</span></label>
                            <select class="form-select" id="age_id" name="age_id" required>
                                <option value="">Выберите возрастную категорию</option>
                                <option value="1">Бэби (2022 - 2021)</option>
                                <option value="2">Мини 1 (2020 - 2019)</option>
                                <option value="7">Мини 2 (2018 - 2017)</option>
                                <option value="3">Ювеналы 1 (2016 - 2015)</option>
                                <option value="8">Ювеналы 2 (2014 - 2013)</option>
                                <option value="4">Юниоры (2012 - 2009)</option>
                                <option value="5">Взрослые (2008 и старше)</option>
                                <option value="6">Смешанная</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Категория</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Выберите категорию</option>
                                <option value="1">Beginners (Начинающие)</option>
                                <option value="2">Basic (Базовый уровень)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="dance_name" class="form-label">Название танца <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="dance_name" name="dance_name" 
                                   value="{{ old('dance_name') }}" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="duration" class="form-label">Длительность номера <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="duration" name="duration" 
                                   value="{{ old('duration') }}" placeholder="3:30" required>
                            <small class="form-text text-muted">Формат: минуты:секунды (например: 3:30)</small>
                        </div>
                    </div>
                    
                    <!-- Дипломы и медали -->
                    <h6 class="text-primary mb-3">Дипломы и медали</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="diploma_quantity" class="form-label">Количество предзаказанных именных дипломов</label>
                            <input type="number" class="form-control" id="diploma_quantity" name="diploma_quantity" 
                                   value="{{ old('diploma_quantity', 0) }}" min="0">
                        </div>
                        <div class="col-md-6">
                            <label for="medal_quantity" class="form-label">Количество предзаказанных медалей</label>
                            <input type="number" class="form-control" id="medal_quantity" name="medal_quantity" 
                                   value="{{ old('medal_quantity', 0) }}" min="0">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="diploma_participants" class="form-label">Список участников на дипломы</label>
                            <textarea class="form-control" id="diploma_participants" name="diploma_participants" rows="3" 
                                      placeholder="Введите имена участников для дипломов, каждый на новой строке">{{ old('diploma_participants') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="medal_participants" class="form-label">Список участников на медали</label>
                            <textarea class="form-control" id="medal_participants" name="medal_participants" rows="3" 
                                      placeholder="Введите имена участников для медалей, каждый на новой строке">{{ old('medal_participants') }}</textarea>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle"></i> Создать регистрацию
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Панель расчета стоимости -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Расчет стоимости</h5>
            </div>
            <div class="card-body">
                <div id="cost-calculation">
                    <p class="text-muted">Выберите мероприятие и номинацию для расчета стоимости</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Автозаполнение
    setupAutocomplete('collective_name', 'collective-suggestions');
    setupAutocomplete('accessory', 'accessory-suggestions');
    setupAutocomplete('leaders', 'leaders-suggestions');
    setupAutocomplete('trainers', 'trainers-suggestions');
    
    // Расчет стоимости
    const eventSelect = document.getElementById('event_id');
    const nominationSelect = document.getElementById('nomination_id');
    const participantsTextarea = document.getElementById('participants');
    const diplomaQuantity = document.getElementById('diploma_quantity');
    const medalQuantity = document.getElementById('medal_quantity');
    
    function calculateCost() {
        const eventId = eventSelect.value;
        const nominationId = nominationSelect.value;
        const participants = participantsTextarea.value.split('\n').filter(p => p.trim()).length;
        const diplomaQty = parseInt(diplomaQuantity.value) || 0;
        const medalQty = parseInt(medalQuantity.value) || 0;
        
        if (!eventId || !nominationId || participants === 0) {
            document.getElementById('cost-calculation').innerHTML = '<p class="text-muted">Выберите мероприятие и номинацию для расчета стоимости</p>';
            return;
        }
        
        fetch('{{ route("api.calculate-cost") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                event_id: eventId,
                nomination_id: nominationId,
                participant_count: participants,
                diploma_quantity: diplomaQty,
                medal_quantity: medalQty
            })
        })
        .then(response => response.json())
        .then(data => {
            const html = `
                <div class="row mb-2">
                    <div class="col-6">Участие (${participants} чел.):</div>
                    <div class="col-6 text-end"><strong>${data.participation.toFixed(2)} ₽</strong></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6">Дипломы (${diplomaQty} шт.):</div>
                    <div class="col-6 text-end"><strong>${data.diplomas.toFixed(2)} ₽</strong></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6">Медали (${medalQty} шт.):</div>
                    <div class="col-6 text-end"><strong>${data.medals.toFixed(2)} ₽</strong></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6"><strong>Итого:</strong></div>
                    <div class="col-6 text-end"><strong class="text-primary">${data.total.toFixed(2)} ₽</strong></div>
                </div>
            `;
            document.getElementById('cost-calculation').innerHTML = html;
        })
        .catch(error => {
            console.error('Ошибка расчета стоимости:', error);
        });
    }
    
    // Обработчики событий
    eventSelect.addEventListener('change', calculateCost);
    nominationSelect.addEventListener('change', calculateCost);
    participantsTextarea.addEventListener('input', calculateCost);
    diplomaQuantity.addEventListener('input', calculateCost);
    medalQuantity.addEventListener('input', calculateCost);
});

function setupAutocomplete(inputId, suggestionsId) {
    const input = document.getElementById(inputId);
    const suggestions = document.getElementById(suggestionsId);
    let timeout;
    
    input.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value;
        
        if (query.length < 2) {
            suggestions.style.display = 'none';
            return;
        }
        
        timeout = setTimeout(() => {
            fetch(`{{ route('api.autocomplete') }}?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    const fieldName = inputId.replace('_', '_');
                    const items = data[fieldName] || [];
                    
                    if (items.length === 0) {
                        suggestions.style.display = 'none';
                        return;
                    }
                    
                    suggestions.innerHTML = items.map(item => 
                        `<div class="autocomplete-suggestion" data-value="${item}">${item}</div>`
                    ).join('');
                    
                    suggestions.style.display = 'block';
                    
                    // Обработчики кликов по предложениям
                    suggestions.querySelectorAll('.autocomplete-suggestion').forEach(item => {
                        item.addEventListener('click', function() {
                            input.value = this.dataset.value;
                            suggestions.style.display = 'none';
                        });
                    });
                });
        }, 300);
    });
    
    // Скрыть предложения при клике вне поля
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !suggestions.contains(e.target)) {
            suggestions.style.display = 'none';
        }
    });
}
</script>
@endpush
