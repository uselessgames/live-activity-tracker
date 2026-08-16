document.getElementById('backBtn').addEventListener('click', () => {
  window.location.href = 'index.php';
});

function formatTime(epochSeconds) {
  const date = new Date(epochSeconds * 1000);
  return date.toLocaleString();
}

function formatDuration(start, end) {
  const seconds = end - start;
  const totalMinutes = Math.floor(seconds / 60);
  const totalHours = Math.floor(totalMinutes / 60);
  const days = Math.floor(totalHours / 24);
  const hours = totalHours % 24;
  const minutes = totalMinutes % 60;
  const remainingSeconds = seconds % 60;

  let result = '';
  if (days > 0) result += `${days}d `;
  if (hours > 0 || days > 0) result += `${hours}h `;
  if (minutes > 0 || hours > 0 || days > 0) result += `${minutes}m `;
  result += `${remainingSeconds}s`;

  return result;
}

function viewActivity(id) {
  alert('View feature not implemented yet');
}

function deleteActivity(id, name) {
  if (!confirm(`Are you sure you want to delete "${name}"?`)) return;

  fetch(`api/activities.php?id=${id}`, {
    method: 'DELETE'
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Activity deleted successfully!');
        location.reload();
      } else {
        alert('Failed to delete activity: ' + (data.error || 'Unknown error'));
      }
    })
    .catch(err => {
      alert('Error deleting activity: ' + err.message);
    });
}

fetch('api/activities.php')
  .then(res => res.json())
  .then(data => {
    const tbody = document.getElementById('activitiesBody');

    if (data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6">No activities found</td></tr>';
      return;
    }

    data.forEach(activity => {
      const row = document.createElement('tr');

      const duration = formatDuration(activity.start_time, activity.end_time);
      const waypointCount = activity.waypoints ? activity.waypoints.length : 0;

      row.innerHTML = `
        <td>${activity.name}</td>
        <td>${activity.tracker_id}</td>
        <td>${formatTime(activity.start_time)}</td>
        <td>${duration}</td>
        <td>${waypointCount}</td>
        <td class="actions">
          <button class="view-btn" onclick="viewActivity(${activity.id})">
            <i class="fa-solid fa-eye"></i> View
          </button>
          <button class="delete-btn" onclick="deleteActivity(${activity.id}, '${activity.name}')">
            <i class="fa-solid fa-trash"></i> Delete
          </button>
        </td>
      `;
      tbody.appendChild(row);
    });
  });
