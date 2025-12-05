const puppeteer = require('puppeteer');

async function testPlatformChart() {
    console.log('Testing Platform Chart on Analytics Page...');

    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    // Login first
    console.log('Logging in...');
    await page.goto('https://cmis-test.kazaaz.com/login', { waitUntil: 'networkidle0' });
    await page.type('input[name="email"]', 'admin@cmis.test');
    await page.type('input[name="password"]', 'password');
    await Promise.all([
        page.click('button[type="submit"]'),
        page.waitForNavigation({ waitUntil: 'networkidle0' })
    ]);

    // Navigate to analytics page
    console.log('Navigating to analytics page...');
    await page.goto('https://cmis-test.kazaaz.com/super-admin/analytics', { waitUntil: 'networkidle0' });

    // Wait for Alpine.js and Chart.js to load
    await page.waitForTimeout(3000);

    // Check API response
    console.log('\n=== API Response Check ===');
    const apiResponse = await page.evaluate(async () => {
        const resp = await fetch('/super-admin/analytics/overview?range=24h', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        return await resp.json();
    });

    const data = apiResponse.data || apiResponse;
    console.log('by_platform data:');
    console.log(JSON.stringify(data.by_platform, null, 2));

    // Check if platform chart canvas exists and has content
    const chartInfo = await page.evaluate(() => {
        const canvas = document.getElementById('platformChart');
        if (!canvas) return { exists: false };

        const ctx = canvas.getContext('2d');
        const chartInstance = Chart.getChart(canvas);

        return {
            exists: true,
            width: canvas.width,
            height: canvas.height,
            hasChartInstance: !!chartInstance,
            chartData: chartInstance ? {
                labels: chartInstance.data.labels,
                datasetData: chartInstance.data.datasets[0]?.data
            } : null
        };
    });

    console.log('\n=== Platform Chart Info ===');
    console.log('Canvas exists:', chartInfo.exists);
    console.log('Has Chart.js instance:', chartInfo.hasChartInstance);
    if (chartInfo.chartData) {
        console.log('Chart labels:', chartInfo.chartData.labels);
        console.log('Chart data:', chartInfo.chartData.datasetData);
    }

    // Check for console errors
    const consoleMessages = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleMessages.push(msg.text());
        }
    });

    // Screenshot
    await page.screenshot({ path: '/tmp/analytics-platform-chart.png', fullPage: true });
    console.log('\nScreenshot saved to /tmp/analytics-platform-chart.png');

    if (consoleMessages.length > 0) {
        console.log('\n=== Console Errors ===');
        consoleMessages.forEach(msg => console.log(msg));
    }

    await browser.close();
    console.log('\nTest completed!');
}

testPlatformChart().catch(console.error);
