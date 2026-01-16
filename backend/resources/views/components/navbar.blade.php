<style>
  .navbar-links {
    display: flex;
    align-items: center;
  }
  @media (max-width: 800px) {
    .navbar-links {
      display: grid !important;
      grid-template-columns: repeat(3, 1fr);
      grid-template-rows: repeat(2, auto);
      gap: 0.5rem;
      width: 100%;
    }
    .navbar-links a {
      width: 100%;
      border-left: none !important;
      border-right: none !important;
      border-top: 1px solid #eee;
      border-bottom: 1px solid #eee;
      margin-left: 0 !important;
      text-align: center;
    }
  }
</style>
<nav style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #f5f5f5; border-bottom: 1px solid #ddd;">
  <div class="navbar-links">
    <a href="{{ url('/admin/dashboard') }}" style="text-decoration: none; font-weight: bold; color: #333; padding: 0.5rem 1.5rem; border-left: 1px solid #ccc; border-right: 1px solid #ccc;">Dashboard</a>
    <a href="{{ url('/admin/members') }}" style="text-decoration: none; color: #333; font-weight: 500; padding: 0.5rem 1rem; border-left: 1px solid #eee; border-right: 1px solid #eee; margin-left: 0.5rem;">Members</a>
    <a href="{{ url('/admin/bookings/create') }}" style="text-decoration: none; color: #333; font-weight: 500; padding: 0.5rem 1rem; border-left: 1px solid #eee; border-right: 1px solid #eee; margin-left: 0.5rem;">Book Events</a>
    <a href="{{ url('/admin/halls') }}" style="text-decoration: none; color: #333; font-weight: 500; padding: 0.5rem 1rem; border-left: 1px solid #eee; border-right: 1px solid #eee; margin-left: 0.5rem;">Halls</a>
    <a href="{{ url('/admin/bookings') }}" style="text-decoration: none; color: #333; font-weight: 500; padding: 0.5rem 1rem; border-left: 1px solid #eee; border-right: 1px solid #eee; margin-left: 0.5rem;">Bookings</a>
    <a href="{{ url('/admin/payments') }}" style="text-decoration: none; color: #333; font-weight: 500; padding: 0.5rem 1rem; border-left: 1px solid #eee; border-right: 1px solid #eee; margin-left: 0.5rem;">Payments</a>
    <a href="{{ url('/admin/logs') }}" style="text-decoration: none; color: #333; font-weight: 500; padding: 0.5rem 1rem; border-left: 1px solid #eee; border-right: 1px solid #eee; margin-left: 0.5rem;">Logs</a>
    <a href="{{ route('admin.admins') }}" style="text-decoration: none; color: #333; font-weight: 500; padding: 0.5rem 1rem; border-left: 1px solid #eee; border-right: 1px solid #eee; margin-left: 0.5rem;">Add Admins</a>
  </div>
  <form method="POST" action="{{ url('/admin/logout') }}">
    @csrf
    <button type="submit" style="background: #e74c3c; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">
      Logout
    </button>
  </form>
</nav>
