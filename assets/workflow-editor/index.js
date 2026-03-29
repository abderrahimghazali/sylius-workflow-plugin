import { createRoot } from 'react-dom/client';
import App from './App';

const container = document.getElementById('sylius-workflow-editor');

if (container) {
    const workflowId = container.dataset.workflowId;
    const apiUrl = container.dataset.apiUrl;
    const initialName = container.dataset.workflowName || 'Untitled Workflow';
    const initialStatus = container.dataset.workflowStatus || 'draft';
    const runsUrl = container.dataset.runsUrl || '';
    const backUrl = container.dataset.backUrl || '';

    let initialGraph = { nodes: [], edges: [] };
    try {
        initialGraph = JSON.parse(container.dataset.graph || '{}');
    } catch (e) {
        console.error('Failed to parse graph JSON:', e);
    }

    const root = createRoot(container);
    root.render(
        <App
            initialGraph={initialGraph}
            workflowId={workflowId}
            apiUrl={apiUrl}
            initialName={initialName}
            initialStatus={initialStatus}
            runsUrl={runsUrl}
            backUrl={backUrl}
        />
    );
}
