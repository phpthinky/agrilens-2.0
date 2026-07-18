<ul class="nav flex-column mt-3">
    <!-- Dashboard -->
    <li class="sidebar-header">🏠 Dashboard</li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
           href="{{ route('admin.dashboard') }}">
            <i class="fas fa-tachometer-alt me-2"></i> OMA Dashboard
        </a>
    </li>
    
    <!-- Farm Management -->
    <li class="sidebar-header">🌾 Farm Management</li>
    
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.farms.index') ? 'active' : '' }}" 
           href="{{ route('admin.farms.index') }}">
            <i class="fas fa-tractor me-2"></i> View All Farms
        </a>
    </li>
    
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.farms.create') ? 'active' : '' }}" 
           href="{{ route('admin.farms.create') }}">
            <i class="fas fa-tractor me-2"></i> Create Farms
        </a>
    </li>
    
    <!-- Farm Management -->
    <li class="sidebar-header">🌾 Farmer Management</li>
    
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.farmers.*') ? 'active' : '' }}" 
           href="{{ route('admin.farmers.index') }}">
            <i class="fas fa-users me-2"></i> View All Farmers
        </a>
    </li>
    
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.farmers.create') ? 'active' : '' }}" 
           href="{{ route('admin.farmers.create') }}">
            <i class="fas fa-users me-2"></i> Create Farmers
        </a>
    </li>
    <!-- Soil Information -->
    <li class="sidebar-header">🧪 Soil Information</li>
    
    
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.soil-tests.index') ? 'active' : '' }}"
           href="{{ route('admin.soil-tests.index') }}">
            <i class="fas fa-list me-2"></i> All Soil Tests
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.soil-test-schedules.*') ? 'active' : '' }}"
           href="{{ route('admin.soil-test-schedules.index') }}">
            <i class="fas fa-calendar-alt me-2"></i> Soil Test Schedules
        </a>
    </li>
    
    <li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.multi-temporal.*') ? 'active' : '' }}" 
       href="{{ route('admin.multi-temporal.index') }}">
        <i class="fas fa-chart-line me-2"></i> Multi-Temporal Analysis
    </a>
</li>
    <!-- Map Viewer -->
    <li class="sidebar-header">🗺️ Map Viewer</li>
    
    <li class="nav-item">
        <a class="nav-link" href="{{ route('public.map') }}" title="Coming Soon">
            <i class="fas fa-map me-2"></i> Interactive Map
        </a>
    </li>
    
    
    <!-- Reports -->
 <!-- Reports -->
<li class="sidebar-header">📊 Reports</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}" 
       href="{{ route('admin.reports.index') }}">
        <i class="fas fa-chart-bar me-2"></i> Generate Reports
    </a>
</li>
{{-- 
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('reports.barangay') ? 'active' : '' }}" 
       href="{{ route('admin.reports.barangay') }}">
        <i class="fas fa-chart-bar me-2"></i> Barangay Reports
    </a>
</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('reports.crop-suitability') ? 'active' : '' }}" 
       href="{{ route('admin.reports.crop-suitability') }}">
        <i class="fas fa-seedling me-2"></i> Crop Suitability Reports
    </a>
</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('reports.ph-summary') ? 'active' : '' }}" 
       href="{{ route('admin.reports.ph-summary') }}">
        <i class="fas fa-vial me-2"></i> pH Summary Reports
    </a>
</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('reports.farm') ? 'active' : '' }}" 
       href="{{ route('admin.reports.farm') }}">
        <i class="fas fa-file-alt me-2"></i> Farm Reports
    </a>
</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('reports.municipality-summary') ? 'active' : '' }}" 
       href="{{ route('admin.reports.municipality-summary') }}">
        <i class="fas fa-city me-2"></i> Municipality Summary
    </a>
</li>
  --}}  
{{-- 

    <!-- Inquiries & Feedback -->
    <li class="sidebar-header">📧 Inquiries & Feedback</li>
    
    <li class="nav-item">
        <a class="nav-link disabled" href="#" title="Coming Soon">
            <i class="fas fa-envelope me-2"></i> View Inquiries
            <span class="coming-soon-tag">Soon</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a class="nav-link disabled" href="#" title="Coming Soon">
            <i class="fas fa-comment me-2"></i> View Feedback
            <span class="coming-soon-tag">Soon</span>
        </a>
    </li>
  --}}  
    
    <!-- Settings -->
    <li class="sidebar-header">⚙️ Settings</li>
    
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.barangays.*') ? 'active' : '' }}" 
           href="{{ route('admin.barangays.index') }}">
            <i class="fas fa-map-marker-alt me-2"></i> Barangay Management
        </a>
    </li>
    
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('users.management.*') ? 'active' : '' }}" 
           href="{{ route('users.management.index') }}">
            <i class="fas fa-user-cog me-2"></i> User Management

        </a>
    </li>
       
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('crops.*') ? 'active' : '' }}" 
           href="{{ route('admin.crops.index') }}">
            <i class="fas fa-leaf me-2"></i> Crop Management

        </a>
    </li>
    
    <!-- Account Settings -->
<li class="sidebar-header">👤 Account</li>

<li class="nav-item">
    <a class="nav-link" href="{{ route('admin.profile.show') }}">
        <i class="fas fa-user-circle me-2"></i> My Profile
    </a>
</li>

<li class="nav-item">
    <a class="nav-link" href="{{ route('admin.profile.change-password') }}">
        <i class="fas fa-key me-2"></i> Change Password
    </a>
</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('help') ? 'active' : '' }}" href="{{ route('help') }}">
        <i class="fas fa-question-circle me-2"></i> Help & User Guide
    </a>
</li>

<li class="nav-item">
    <a class="nav-link" href="{{ route('logout') }}" 
       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        <i class="fas fa-sign-out-alt me-2"></i> Logout
    </a>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>
</li>
</ul>