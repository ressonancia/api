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
                stage('Test Ressonance'){
                    steps {
                        sh  'cp .env.example .env'
                        sh  'cp phpunit.ci.xml phpunit.xml'
                        sh  'php artisan key:generate'
                        sh  'php artisan passport:keys --ansi'
                        sh  'php artisan test'
                    }
                }
            }
        }
        // stage('Deploy Resonance') {
        //     steps {
        //         withCredentials([sshUserPrivateKey(credentialsId: 'ressonance-private-key', keyFileVariable: 'SSH_KEY')]) {
        //             sh '''
        //                 chmod 600 "$SSH_KEY"
        //                 eval "$(ssh-agent -s)"
        //                 ssh-add "$SSH_KEY"
        //                 ./vendor/bin/envoy run deploy
        //             '''
        //         }
        //     }
        // }
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