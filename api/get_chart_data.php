<?php
/**
 * API endpoint for fetching chart data
 * Used for real-time chart updates
 */
header('Content-Type: application/json');
require_once '../config/database.php';

$conn = getDBConnection();

// Get overall statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed,
        SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced
    FROM email_subscriptions
");
$overall_stats = $stats->fetch_assoc();

// Get monthly stats
$monthly_stats = $conn->query("
    SELECT 
        DATE_FORMAT(subscribed_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM email_subscriptions
    GROUP BY DATE_FORMAT(subscribed_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");

$months = [];
$counts = [];
while ($row = $monthly_stats->fetch_assoc()) {
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $counts[] = $row['count'];
}
$months = array_reverse($months);
$counts = array_reverse($counts);

closeDBConnection($conn);

echo json_encode([
    'stats' => $overall_stats,
    'status' => [
        'active' => intval($overall_stats['active']),
        'unsubscribed' => intval($overall_stats['unsubscribed']),
        'bounced' => intval($overall_stats['bounced'])
    ],
    'monthly' => [
        'labels' => $months,
        'counts' => $counts
    ]
]);
?>

