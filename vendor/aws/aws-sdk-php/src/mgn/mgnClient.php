<?php
namespace Aws\mgn;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Application Migration Service** service.
 * @method \Aws\Result changeServerLifeCycleState(array $args = [])
 * @method \GuzzleHttp\Promise\Promise changeServerLifeCycleStateAsync(array $args = [])
 * @method \Aws\Result createReplicationConfigurationTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createReplicationConfigurationTemplateAsync(array $args = [])
 * @method \Aws\Result deleteJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteJobAsync(array $args = [])
 * @method \Aws\Result deleteReplicationConfigurationTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteReplicationConfigurationTemplateAsync(array $args = [])
 * @method \Aws\Result deleteSourceServer(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteSourceServerAsync(array $args = [])
 * @method \Aws\Result describeJobLogItems(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeJobLogItemsAsync(array $args = [])
 * @method \Aws\Result describeJobs(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeJobsAsync(array $args = [])
 * @method \Aws\Result describeReplicationConfigurationTemplates(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeReplicationConfigurationTemplatesAsync(array $args = [])
 * @method \Aws\Result describeSourceServers(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeSourceServersAsync(array $args = [])
 * @method \Aws\Result disconnectFromService(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disconnectFromServiceAsync(array $args = [])
 * @method \Aws\Result finalizeCutover(array $args = [])
 * @method \GuzzleHttp\Promise\Promise finalizeCutoverAsync(array $args = [])
 * @method \Aws\Result getLaunchConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getLaunchConfigurationAsync(array $args = [])
 * @method \Aws\Result getReplicationConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getReplicationConfigurationAsync(array $args = [])
 * @method \Aws\Result initializeService(array $args = [])
 * @method \GuzzleHttp\Promise\Promise initializeServiceAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result markAsArchived(array $args = [])
 * @method \GuzzleHttp\Promise\Promise markAsArchivedAsync(array $args = [])
 * @method \Aws\Result retryDataReplication(array $args = [])
 * @method \GuzzleHttp\Promise\Promise retryDataReplicationAsync(array $args = [])
 * @method \Aws\Result startCutover(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startCutoverAsync(array $args = [])
 * @method \Aws\Result startTest(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startTestAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result terminateTargetInstances(array $args = [])
 * @method \GuzzleHttp\Promise\Promise terminateTargetInstancesAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateLaunchConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateLaunchConfigurationAsync(array $args = [])
 * @method \Aws\Result updateReplicationConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateReplicationConfigurationAsync(array $args = [])
 * @method \Aws\Result updateReplicationConfigurationTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateReplicationConfigurationTemplateAsync(array $args = [])
 */
class mgnClient extends AwsClient {}
