<?php
// public/handover/sections/step_8.php - Technical
?>

<div class="alert alert-info">
    <h6><i class="fas fa-cogs"></i> Technical</h6>
    <p class="mb-0">Document technical specifications and infrastructure details required for operations.</p>
</div>

<div class="row">
    <div class="col-12">
        <!-- Technical Service Details -->
        <div class="mb-4">
            <h6>Technical Service Details</h6>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="technical_service_offering" id="technicalServiceOffering" placeholder="Technical Service Offering" value="<?php echo htmlspecialchars($existing_data['technical_service_offering'] ?? ''); ?>">
                        <label for="technicalServiceOffering">Technical Service Offering (from TE)</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select class="form-select" name="business_criticality" id="businessCriticality">
                            <option value="">Select criticality...</option>
                            <option value="1" <?php echo ($existing_data['business_criticality'] ?? '') === '1' ? 'selected' : ''; ?>>1 - Critical</option>
                            <option value="2" <?php echo ($existing_data['business_criticality'] ?? '') === '2' ? 'selected' : ''; ?>>2 - High</option>
                            <option value="3" <?php echo ($existing_data['business_criticality'] ?? '') === '3' ? 'selected' : ''; ?>>3 - Medium</option>
                            <option value="4" <?php echo ($existing_data['business_criticality'] ?? '') === '4' ? 'selected' : ''; ?>>4 - Low</option>
                            <option value="5" <?php echo ($existing_data['business_criticality'] ?? '') === '5' ? 'selected' : ''; ?>>5 - Very Low</option>
                        </select>
                        <label for="businessCriticality">Business Criticality (1-5)</label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select class="form-select" name="environment" id="environment">
                            <option value="">Select environment...</option>
                            <option value="Production" <?php echo ($existing_data['environment'] ?? '') === 'Production' ? 'selected' : ''; ?>>Production</option>
                            <option value="Test" <?php echo ($existing_data['environment'] ?? '') === 'Test' ? 'selected' : ''; ?>>Test</option>
                            <option value="Development" <?php echo ($existing_data['environment'] ?? '') === 'Development' ? 'selected' : ''; ?>>Development</option>
                        </select>
                        <label for="environment">Environment</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select class="form-select" name="install_type" id="installType">
                            <option value="">Select install type...</option>
                            <option value="Hybrid" <?php echo ($existing_data['install_type'] ?? '') === 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                            <option value="Azure" <?php echo ($existing_data['install_type'] ?? '') === 'Azure' ? 'selected' : ''; ?>>Azure</option>
                            <option value="OnPrem" <?php echo ($existing_data['install_type'] ?? '') === 'OnPrem' ? 'selected' : ''; ?>>OnPrem</option>
                            <option value="Externally hosted" <?php echo ($existing_data['install_type'] ?? '') === 'Externally hosted' ? 'selected' : ''; ?>>Externally hosted</option>
                            <option value="Plugin" <?php echo ($existing_data['install_type'] ?? '') === 'Plugin' ? 'selected' : ''; ?>>Plugin</option>
                        </select>
                        <label for="installType">Install Type</label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select class="form-select" name="architecture_type" id="architectureType">
                            <option value="">Select architecture...</option>
                            <option value="Infrastructure" <?php echo ($existing_data['architecture_type'] ?? '') === 'Infrastructure' ? 'selected' : ''; ?>>Infrastructure</option>
                            <option value="SaaS" <?php echo ($existing_data['architecture_type'] ?? '') === 'SaaS' ? 'selected' : ''; ?>>SaaS</option>
                            <option value="PaaS" <?php echo ($existing_data['architecture_type'] ?? '') === 'PaaS' ? 'selected' : ''; ?>>PaaS</option>
                            <option value="IaaS" <?php echo ($existing_data['architecture_type'] ?? '') === 'IaaS' ? 'selected' : ''; ?>>IaaS</option>
                            <option value="Microservices" <?php echo ($existing_data['architecture_type'] ?? '') === 'Microservices' ? 'selected' : ''; ?>>Microservices</option>
                            <option value="Monolithic" <?php echo ($existing_data['architecture_type'] ?? '') === 'Monolithic' ? 'selected' : ''; ?>>Monolithic</option>
                        </select>
                        <label for="architectureType">Architecture Type</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="date" class="form-control" name="first_day_production" id="firstDayProduction" value="<?php echo htmlspecialchars($existing_data['first_day_production'] ?? ''); ?>">
                        <label for="firstDayProduction">First day in production</label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select class="form-select" name="source_code_azure_devops" id="sourceCodeAzureDevops">
                            <option value="">Select...</option>
                            <option value="Yes" <?php echo ($existing_data['source_code_azure_devops'] ?? '') === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                            <option value="No" <?php echo ($existing_data['source_code_azure_devops'] ?? '') === 'No' ? 'selected' : ''; ?>>No</option>
                            <option value="Partial" <?php echo ($existing_data['source_code_azure_devops'] ?? '') === 'Partial' ? 'selected' : ''; ?>>Partial</option>
                        </select>
                        <label for="sourceCodeAzureDevops">Source code loaded into Azure DevOps</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select class="form-select" name="application_portfolio" id="applicationPortfolio">
                            <option value="">Select portfolio...</option>
                            <?php for ($i = 1; $i <= 16; $i++): ?>
                                <option value="S<?php echo $i; ?>" <?php echo ($existing_data['application_portfolio'] ?? '') === "S$i" ? 'selected' : ''; ?>>S<?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        <label for="applicationPortfolio">Application Portfolio (S1-S16)</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Technical Infrastructure -->
        <div class="mb-4">
            <h6>Technical Infrastructure</h6>
            
            <div class="form-floating mb-3">
                <textarea class="form-control" name="infrastructure_details" id="infrastructureDetails" style="height: 100px" placeholder="Describe technical infrastructure..."><?php echo htmlspecialchars($existing_data['infrastructure_details'] ?? ''); ?></textarea>
                <label for="infrastructureDetails">Infrastructure details and requirements</label>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="server_specifications" id="serverSpecifications" placeholder="Server specifications" value="<?php echo htmlspecialchars($existing_data['server_specifications'] ?? ''); ?>">
                        <label for="serverSpecifications">Server specifications</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="database_details" id="databaseDetails" placeholder="Database details" value="<?php echo htmlspecialchars($existing_data['database_details'] ?? ''); ?>">
                        <label for="databaseDetails">Database details</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Network and Connectivity -->
        <div class="mb-4">
            <h6>Network and Connectivity</h6>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="network_requirements" id="networkRequirements" placeholder="Network requirements" value="<?php echo htmlspecialchars($existing_data['network_requirements'] ?? ''); ?>">
                        <label for="networkRequirements">Network requirements</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="firewall_rules" id="firewallRules" placeholder="Firewall rules" value="<?php echo htmlspecialchars($existing_data['firewall_rules'] ?? ''); ?>">
                        <label for="firewallRules">Firewall rules and ports</label>
                    </div>
                </div>
            </div>

            <div class="form-floating mb-3">
                <textarea class="form-control" name="integration_points" id="integrationPoints" style="height: 80px" placeholder="Integration points..."><?php echo htmlspecialchars($existing_data['integration_points'] ?? ''); ?></textarea>
                <label for="integrationPoints">Integration points and dependencies</label>
            </div>
        </div>

        <div class="alert alert-light">
            <h6><i class="fas fa-info-circle"></i> Help text</h6>
            <ul class="mb-0">
                <li><strong>Business Criticality:</strong> 1 = Critical (highest), 5 = Very Low (lowest)</li>
                <li><strong>Technical Service Offering:</strong> Reference from Technical Environment (TE)</li>
                <li><strong>Application Portfolio:</strong> Classification from S1 to S16</li>
                <li>Include all technical details needed for operations and maintenance</li>
                <li>Document infrastructure dependencies and integration requirements</li>
            </ul>
        </div>
    </div>
</div>
