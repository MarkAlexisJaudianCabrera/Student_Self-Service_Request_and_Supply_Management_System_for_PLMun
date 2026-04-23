SELECT * FROM requesttb 
WHERE status IN ('Unpaid', 'Paid', 'Rejected', 'Completed')
ORDER BY date_requested DESC