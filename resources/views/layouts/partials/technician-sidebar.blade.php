<ul class="nav flex-column mt-3">
    <!-- Dashboard -->
    <li class="sidebar-header">🏠 Dashboard</li>
    
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('technician.dashboard') ? 'active' : '' }}" 
           href="{{ route('technician.dashboard') }}">
            <i class="fas fa-chart-line me-2"></i> Technician Dashboard
        </a>
    </li>
    
    <!-- Farm Management -->
    <li class="sidebar-header">🌾 Farm Management</li>
    
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('technician.farms.index') ? 'active' : '' }}" 
           href="{{ route('technician.farms.index') }}">
            <i class="fas fa-tractor me-2"></i> View All Farms
        </a>
    </li>
    
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('technician.farms.create') ? 'active' : '' }}" 
           href="{{ route('technician.farms.create') }}">
            <i class="fas fa-plus-circle me-2"></i> Add Farm
        </a>
    </li>
    
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('technician.farmers.*') ? 'active' : '' }}" 
           href="{{ route('technician.farmers.index') }}">
            <i class="fas fa-users me-2"></i> Manage Farmers
        </a>
    </li>
    
    <!-- Soil Information -->
    <li class="sidebar-header">🧪 Soil Information</li>
    
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('technician.soil-tests.create') ? 'active' : '' }}" 
           href="{{ route('technician.soil-tests.create') }}">
            <i class="fas fa-flask me-2"></i> Soil Data Entry
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('technician.my-submissions') ? 'active' : '' }}" 
           href="{{ route('technician.my-submissions') }}">
            <i class="fas fa-history me-2"></i> My Submissions
        </a>
    </li>
    
   
   <li class="nav-item">
    <a class="nav-link {{ request()->routeIs('technician.soil-tests.import.*') ? 'active' : '' }}" 
       href="{{ route('technician.soil-tests.import.index') }}">
        <i class="fas fa-file-import me-2"></i> Import Arduino Data
    </a>
</li>
        <!-- NEW: Multi-Temporal Analysis -->
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('technician.multi-temporal.*') ? 'active' : '' }}"
           href="{{ route('technician.multi-temporal.index') }}">
            <i class="fas fa-chart-line me-2"></i> Multi-Temporal Analysis
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('technician.soil-test-schedules.*') ? 'active' : '' }}"
           href="{{ route('technician.soil-test-schedules.index') }}">
            <i class="fas fa-calendar-alt me-2"></i> My Schedules
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
    <li class="sidebar-header">📊 Reports</li>

    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('technician.reports.*') ? 'active' : '' }}"
           href="{{ route('technician.reports.index') }}">
            <i class="fas fa-chart-bar me-2"></i> Generate Reports
        </a>
    </li>
    
    <!-- Account Settings -->
    <li class="sidebar-header">👤 Account</li>
    
    <li class="nav-item">
        <a class="nav-link  {{ request()->routeIs('technician.profile.*') ? 'active' : '' }}" href="{{ route('technician.profile') }}" title="Coming Soon">
            <i class="fas fa-user-circle me-2"></i> My Profile
        </a>
    </li>
    
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('technician.profile.*') ? 'active' : '' }}" href="{{ route('technician.change-password') }}" title="Coming Soon">
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