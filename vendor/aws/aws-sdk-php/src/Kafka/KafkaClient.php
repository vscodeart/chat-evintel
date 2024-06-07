<?php
namespace Aws\Kafka;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Managed Streaming for Kafka** service.
 * @method \Aws\Result batchAssociateScramSecret(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchAssociateScramSecretAsync(array $args = [])
 * @method \Aws\Result createCluster(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createClusterAsync(array $args = [])
 * @method \Aws\Result createConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createConfigurationAsync(array $args = [])
 * @method \Aws\Result deleteCluster(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteClusterAsync(array $args = [])
 * @method \Aws\Result deleteConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteConfigurationAsync(array $args = [])
 * @method \Aws\Result describeCluster(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeClusterAsync(array $args = [])
 * @method \Aws\Result describeClusterOperation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeClusterOperationAsync(array $args = [])
 * @method \Aws\Result describeConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeConfigurationAsync(array $args = [])
 * @method \Aws\Result describeConfigurationRevision(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeConfigurationRevisionAsync(array $args = [])
 * @method \Aws\Result batchDisassociateScramSecret(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchDisassociateScramSecretAsync(array $args = [])
 * @method \Aws\Result getBootstrapBrokers(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getBootstrapBrokersAsync(array $args = [])
 * @method \Aws\Result getCompatibleKafkaVersions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCompatibleKafkaVersionsAsync(array $args = [])
 * @method \Aws\Result listClusterOperations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listClusterOperationsAsync(array $args = [])
 * @method \Aws\Result listClusters(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listClustersAsync(array $args = [])
 * @method \Aws\Result listConfigurationRevisions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listConfigurationRevisionsAsync(array $args = [])
 * @method \Aws\Result listConfigurations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listConfigurationsAsync(array $args = [])
 * @method \Aws\Result listKafkaVersions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listKafkaVersionsAsync(array $args = [])
 * @method \Aws\Result listNodes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listNodesAsync(array $args = [])
 * @method \Aws\Result listScramSecrets(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listScramSecretsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result rebootBroker(array $args = [])
 * @method \GuzzleHttp\Promise\Promise rebootBrokerAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateBrokerCount(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateBrokerCountAsync(array $args = [])
 * @method \Aws\Result updateBrokerType(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateBrokerTypeAsync(array $args = [])
 * @method \Aws\Result updateBrokerStorage(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateBrokerStorageAsync(array $args = [])
 * @method \Aws\Result updateConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateConfigurationAsync(array $args = [])
 * @method \Aws\Result updateClusterConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateClusterConfigurationAsync(array $args = [])
 * @method \Aws\Result updateClusterKafkaVersion(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateClusterKafkaVersionAsync(array $args = [])
 * @method \Aws\Result updateConnectivity(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateConnectivityAsync(array $args = [])
 * @method \Aws\Result updateMonitoring(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateMonitoringAsync(array $args = [])
 * @method \Aws\Result updateSecurity(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateSecurityAsync(array $args = [])
 */
class KafkaClient extends AwsClient {}
