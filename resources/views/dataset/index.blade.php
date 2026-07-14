@extends('layouts.app')

@section('page_title', 'Dataset Gaya Penulisan & Latihan')
@section('page_description', 'Latih kecerdasan buatan dengan contoh laporan harian dan rekomendasi latihan agar hasilnya sesuai gaya Anda.')

@section('content')
<section class="panel active" id="tab-dataset">
    <!-- Tambah Dataset Card -->
    <div class="card">
        <h2>Tambah Contoh Referensi</h2>
        <p class="desc">Paste laporan lama atau rekomendasi latihan di sini. Pilih tipe bagian laporan yang sesuai agar AI tahu gaya penulisan spesifik untuk tiap bagian.</p>
        
        <form action="{{ route('dataset.store') }}" method="POST">
            @csrf
            
            <div style="margin-bottom: 14px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                <span style="font-size: 13.5px; font-weight: 600; color: var(--ink);">Bahasa Contoh:</span>
                <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13.5px; cursor: pointer; font-weight: normal; margin: 0; color: var(--ink);">
                    <input type="radio" name="language" value="id" checked style="margin: 0; width: 16px; height: 16px; accent-color: var(--teal);">
                    Bahasa Indonesia
                </label>
                <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13.5px; cursor: pointer; font-weight: normal; margin: 0; color: var(--ink);">
                    <input type="radio" name="language" value="en" style="margin: 0; width: 16px; height: 16px; accent-color: var(--teal);">
                    English (Bahasa Inggris)
                </label>
            </div>

            <div style="display: grid; grid-template-columns: 1fr; gap: 14px; margin-bottom: 14px; max-width: 600px;">
                <div>
                    <label for="sectionTypeSelect" style="font-weight: 600; font-size: 13.5px; display: block; margin-bottom: 6px;">Tipe Bagian Laporan:</label>
                    <select name="section_type" id="sectionTypeSelect" style="background: #FCFAF6; border: 1.5px solid var(--line); border-radius: 8px; padding: 10px 12px; font-size: 14px; font-family: inherit; color: var(--ink); cursor: pointer; width: 100%; box-sizing: border-box; height: 42px;">
                        <option value="overview">Overview (Progress & Behavior Harian)</option>
                        <option value="teachers_note">Teacher's Note (Observasi Karakter/Fokus)</option>
                        <option value="training_recommendation">Training Recommendation (Rekomendasi Latihan)</option>
                        <option value="parent_note">Parent Note (Saran Pendampingan Orang Tua)</option>
                    </select>
                </div>

                <div id="categoryWrapper" style="display: none;">
                    <label for="categorySelect" style="font-weight: 600; font-size: 13.5px; display: block; margin-bottom: 6px;">Kategori Rekomendasi Latihan:</label>
                    <select name="category" id="categorySelect" style="background: #FCFAF6; border: 1.5px solid var(--line); border-radius: 8px; padding: 10px 12px; font-size: 14px; font-family: inherit; color: var(--ink); cursor: pointer; width: 100%; box-sizing: border-box; height: 42px;">
                        <option value="kreativitas">Kreativitas (Game Design, Visual, Modifikasi Bebas)</option>
                        <option value="logika_terstruktur">Logika Terstruktur (Algoritma, Loop, Kondisi)</option>
                        <option value="eksperimen">Eksperimen (Eksplorasi Mandiri, Fitur Baru)</option>
                        <option value="coding_dasar">Coding Dasar (Sintaks Basic, Navigasi Editor, Ketik)</option>
                    </select>
                </div>
            </div>

            <textarea name="body" style="min-height: 120px;" placeholder="Tulis contoh paragraf di sini..." required></textarea>
            <button type="submit" class="btn">Tambah ke dataset</button>
        </form>
    </div>

    <!-- Contoh Tersimpan Card -->
    <div class="card">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; border-bottom: 1px solid var(--line); padding-bottom: 16px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <h2 style="margin: 0;">Contoh Tersimpan (<span id="totalCount">{{ $dataset->count() + $recommendationDataset->count() }}</span>)</h2>

                <!-- Batch Actions (tampil saat ada item terpilih) -->
                <div id="batchActionContainer" style="display: none; align-items: center; gap: 10px;">
                    <span style="font-size: 13px; color: var(--muted); font-weight: 600;"><span id="selectedCount">0</span> item terpilih</span>
                    <button type="button" class="btn danger" style="padding: 5px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;" onclick="triggerDatasetBatchDelete()">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        Hapus Terpilih
                    </button>
                </div>
            </div>

            @if(!$dataset->isEmpty() || !$recommendationDataset->isEmpty())
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 600; color: var(--ink); margin: 0; background: #FCFAF6; border: 1.5px solid var(--line); border-radius: 6px; padding: 5px 12px; user-select: none;">
                    <input type="checkbox" id="selectAllDataset" style="margin: 0; width: 16px; height: 16px; accent-color: var(--teal); cursor: pointer;">
                    Pilih Semua
                </label>
            @endif
        </div>

        <!-- Filters Row -->
        <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                <!-- Filter Bahasa -->
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span style="font-size: 12px; font-weight: 600; color: var(--muted); width: 80px;">Bahasa:</span>
                    <div class="sub-tabs lang-filters" style="display: flex; gap: 4px; background: #FAF9F6; border: 1.5px solid var(--line); padding: 3px; border-radius: 6px;">
                        <button class="sub-tab-btn active" data-filter="all" onclick="setFilter('lang', 'all')" style="padding: 3px 8px; font-size: 11.5px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; background: var(--teal); color: white; transition: all 0.2s;">Semua</button>
                        <button class="sub-tab-btn" data-filter="id" onclick="setFilter('lang', 'id')" style="padding: 3px 8px; font-size: 11.5px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; background: transparent; color: var(--muted); transition: all 0.2s;">Bahasa Indonesia</button>
                        <button class="sub-tab-btn" data-filter="en" onclick="setFilter('lang', 'en')" style="padding: 3px 8px; font-size: 11.5px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; background: transparent; color: var(--muted); transition: all 0.2s;">English</button>
                    </div>
                </div>

                <!-- Filter Tipe Bagian -->
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span style="font-size: 12px; font-weight: 600; color: var(--muted); width: 80px;">Bagian:</span>
                    <div class="sub-tabs type-filters" style="display: flex; gap: 4px; background: #FAF9F6; border: 1.5px solid var(--line); padding: 3px; border-radius: 6px;">
                        <button class="sub-tab-btn active" data-filter="all" onclick="setFilter('type', 'all')" style="padding: 3px 8px; font-size: 11.5px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; background: var(--teal); color: white; transition: all 0.2s;">Semua Bagian</button>
                        <button class="sub-tab-btn" data-filter="overview" onclick="setFilter('type', 'overview')" style="padding: 3px 8px; font-size: 11.5px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; background: transparent; color: var(--muted); transition: all 0.2s;">Overview</button>
                        <button class="sub-tab-btn" data-filter="teachers_note" onclick="setFilter('type', 'teachers_note')" style="padding: 3px 8px; font-size: 11.5px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; background: transparent; color: var(--muted); transition: all 0.2s;">Teacher's Note</button>
                        <button class="sub-tab-btn" data-filter="training_recommendation" onclick="setFilter('type', 'training_recommendation')" style="padding: 3px 8px; font-size: 11.5px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; background: transparent; color: var(--muted); transition: all 0.2s;">Training Rec</button>
                        <button class="sub-tab-btn" data-filter="parent_note" onclick="setFilter('type', 'parent_note')" style="padding: 3px 8px; font-size: 11.5px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; background: transparent; color: var(--muted); transition: all 0.2s;">Parent Note</button>
                    </div>
                </div>

                <!-- Filter Kategori Rekomendasi -->
                <div id="categoryFilterRow" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span style="font-size: 12px; font-weight: 600; color: var(--muted); width: 80px;">Kategori Rec:</span>
                    <div class="sub-tabs category-filters" style="display: flex; gap: 4px; background: #FAF9F6; border: 1.5px solid var(--line); padding: 3px; border-radius: 6px;">
                        <button class="sub-tab-btn active" data-filter="all" onclick="setFilter('cat', 'all')" style="padding: 3px 8px; font-size: 11.5px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; background: var(--teal); color: white; transition: all 0.2s;">Semua Kategori</button>
                        <button class="sub-tab-btn" data-filter="kreativitas" onclick="setFilter('cat', 'kreativitas')" style="padding: 3px 8px; font-size: 11.5px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; background: transparent; color: var(--muted); transition: all 0.2s;">Kreativitas</button>
                        <button class="sub-tab-btn" data-filter="logika_terstruktur" onclick="setFilter('cat', 'logika_terstruktur')" style="padding: 3px 8px; font-size: 11.5px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; background: transparent; color: var(--muted); transition: all 0.2s;">Logika</button>
                        <button class="sub-tab-btn" data-filter="eksperimen" onclick="setFilter('cat', 'eksperimen')" style="padding: 3px 8px; font-size: 11.5px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; background: transparent; color: var(--muted); transition: all 0.2s;">Eksperimen</button>
                        <button class="sub-tab-btn" data-filter="coding_dasar" onclick="setFilter('cat', 'coding_dasar')" style="padding: 3px 8px; font-size: 11.5px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; background: transparent; color: var(--muted); transition: all 0.2s;">Coding Dasar</button>
                    </div>
                </div>
            </div>
        
        <div id="datasetList">
            @if($dataset->isEmpty() && $recommendationDataset->isEmpty())
                <div class="empty">Belum ada contoh laporan tersimpan.</div>
            @else
                <!-- Loop General Dataset -->
                @foreach($dataset as $entry)
                    <div class="list-item dataset-item lang-{{ $entry->language }} type-{{ $entry->section_type }} cat-all" style="align-items: flex-start; display: flex; justify-content: space-between; border-bottom: 1.5px solid var(--line); padding: 16px 0;">
                        <div style="display: flex; align-items: flex-start; gap: 12px; flex: 1; max-width: 85%;">
                            <input type="checkbox" class="dataset-select-cb" value="{{ $entry->id }}" style="margin-top: 4px; width: 16px; height: 16px; accent-color: var(--teal); cursor: pointer; flex-shrink: 0;">
                            <div class="meta" style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;">
                                    <span class="badge" style="background: {{ $entry->language === 'en' ? '#EBF5FF' : '#E6FFFA' }}; color: {{ $entry->language === 'en' ? '#1E40AF' : '#047857' }}; border: none; font-size: 10.5px; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                                        {{ $entry->language === 'en' ? 'English' : 'Bahasa Indonesia' }}
                                    </span>
                                    <span class="badge" style="background: #F3F4F6; color: #374151; border: none; font-size: 10.5px; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                                        {{ Str::upper(str_replace('_', ' ', $entry->section_type)) }}
                                    </span>
                                </div>
                                <span style="font-size: 13.5px; color: var(--ink); white-space: pre-wrap; line-height: 1.5;">{{ Str::limit($entry->body, 220, '…') }}</span>
                            </div>
                        </div>
                        <button type="button" class="btn danger" style="padding: 6px 10px;" onclick="openDeleteModal('{{ route('dataset.destroy', $entry->id) }}', 'Hapus contoh laporan ini dari dataset?')">Hapus</button>
                    </div>
                @endforeach

                <!-- Loop Recommendation Dataset -->
                @foreach($recommendationDataset as $entry)
                    <div class="list-item dataset-item lang-{{ $entry->language }} type-training_recommendation cat-{{ $entry->category }}" style="align-items: flex-start; display: flex; justify-content: space-between; border-bottom: 1.5px solid var(--line); padding: 16px 0;">
                        <div style="display: flex; align-items: flex-start; gap: 12px; flex: 1; max-width: 85%;">
                            <input type="checkbox" class="dataset-select-cb" value="{{ $entry->id }}" style="margin-top: 4px; width: 16px; height: 16px; accent-color: var(--teal); cursor: pointer; flex-shrink: 0;">
                            <div class="meta" style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;">
                                    <span class="badge" style="background: {{ $entry->language === 'en' ? '#EBF5FF' : '#E6FFFA' }}; color: {{ $entry->language === 'en' ? '#1E40AF' : '#047857' }}; border: none; font-size: 10.5px; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                                        {{ $entry->language === 'en' ? 'English' : 'Bahasa Indonesia' }}
                                    </span>
                                    <span class="badge" style="background: #E0F2FE; color: #0369A1; border: none; font-size: 10.5px; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                                        TRAINING RECOMMENDATION
                                    </span>
                                    <span class="badge" style="background: #FEF3C7; color: #92400E; border: none; font-size: 10.5px; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                                        {{ Str::upper(str_replace('_', ' ', $entry->category)) }}
                                    </span>
                                </div>
                                <span style="font-size: 13.5px; color: var(--ink); white-space: pre-wrap; line-height: 1.5;">{{ Str::limit($entry->body, 220, '…') }}</span>
                            </div>
                        </div>
                        <button type="button" class="btn danger" style="padding: 6px 10px;" onclick="openDeleteModal('{{ route('dataset.destroy', $entry->id) }}', 'Hapus contoh latihan ini dari dataset?')">Hapus</button>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
@endsection


