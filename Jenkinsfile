pipeline {
    agent {
        node 'local-shell'
    }
    environment {
        COMPOSER_NO_INTERACTION = '1'
        DOCKER_NETWORK = "ressonance_api_$BUILD_ID"
    }
    stages {
        stage('Prepare Network and MySQL') {
            steps {
                sh '''
                    docker network create "$DOCKER_NETWORK" || true

                    docker run -d --rm --name mysql \
                        --network "$DOCKER_NETWORK" \
                        -e MYSQL_ROOT_PASSWORD=root \
                        -e MYSQL_DATABASE=ressonance \
                        mysql:8.0

                    echo "Waiting 5 seconds for MySQL to be ready..."
                    sleep 5
                '''
            }
        }
        stage('Run Tests and Deploy') {
            agent {
                docker {
                    image 'convenia/php-full:latest'
                    args "--network ressonance_api_$BUILD_ID"
                }
            }
            stages {
                stage('Install dependencies') {
                    steps {
                        sh 'composer install --no-interaction --prefer-dist --ansi'
                    }
                }
                stage('Test Ressonance API'){
                    steps {
                        sh  'cp .env.example .env'
                        sh  'cp phpunit.ci.xml phpunit.xml'
                        sh  'php artisan key:generate'
                        sh  'php artisan test'
                    }
                }
                stage('Deploy Resonance API') {
                    environment {
                        SSH_KEY_CONTENT = credentials('ressonance-private-key')
                    }
                    steps {
                        sh '''
                            ./vendor/bin/envoy run deploy
                            rm -f /tmp/deploy_key
                        '''
                    }
                }
            }
        }
    }
    post {
        always {
            sh '''
                docker rm -f mysql || true
                docker network rm "$DOCKER_NETWORK" || true
            '''
        }
    }
}